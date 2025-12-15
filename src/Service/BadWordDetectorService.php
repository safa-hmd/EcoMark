<?php
namespace App\Service;

use App\Entity\CensoredText;
use App\Repository\CensoredTextRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BadWordDetectorService
{
    private string $hfApiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CensoredTextRepository $censoredTextRepository,
        private EntityManagerInterface $entityManager,
        string $huggingfaceApiKey = ''
    ) {
        $this->hfApiKey = $huggingfaceApiKey;
    }

    /**
     * Détecte et remplace les bad words avec IA GRATUITE (Hugging Face)
     */
    public function censorBadWords(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        $textHash = md5(trim($text));

        // 1. Vérifier cache BD
        $cached = $this->censoredTextRepository->findByTextHash($textHash);
        
        if ($cached !== null) {
            $cached->incrementUsageCount();
            $this->entityManager->flush();
            return $cached->getCensoredText();
        }

        // 2. Appeler l'IA GRATUITE Hugging Face
        $censoredText = $this->callHuggingFaceAI($text);
        $hasBadWords = ($censoredText !== $text);

        // 3. Sauvegarder dans BD
        $this->saveCensoredText($textHash, $text, $censoredText, $hasBadWords);

        return $censoredText;
    }

    /**
     * Appelle Hugging Face API (GRATUIT) pour censurer
     * SANS API KEY - 30 requêtes/heure gratuit
     */
    private function callHuggingFaceAI(string $text): string
    {
        try {
            // Utiliser Mistral avec API key
            $model = "mistralai/Mistral-7B-Instruct-v0.2";
            
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Ajouter l'API key si disponible (augmente limite à 1000+ req/h)
            if (!empty($this->hfApiKey)) {
                $headers['Authorization'] = 'Bearer ' . $this->hfApiKey;
            }

            $prompt = $this->buildPrompt($text);

            $response = $this->httpClient->request('POST', 
                "https://api-inference.huggingface.co/models/{$model}", 
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $prompt,
                        'parameters' => [
                            'max_new_tokens' => 500,
                            'temperature' => 0.1,
                            'return_full_text' => false,
                        ]
                    ],
                    'timeout' => 30,
                ]
            );

            $data = $response->toArray();
            
            if (isset($data[0]['generated_text'])) {
                $result = trim($data[0]['generated_text']);
                return $this->extractCensoredText($result, $text);
            }
            
            return $this->fallbackCensorship($text);
            
        } catch (\Exception $e) {
            error_log('Hugging Face AI Error: ' . $e->getMessage());
            return $this->fallbackCensorship($text);
        }
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
<s>[INST] Tu es un modérateur de contenu. Ta tâche est de remplacer UNIQUEMENT les mots vulgaires, insultants ou offensants par des étoiles (***).

RÈGLES:
1. Remplace SEULEMENT les insultes et vulgarités par ***
2. Détecte les mots en français, arabe (lettres latines), anglais et darija
3. Ne change RIEN d'autre (ponctuation, structure, mots normaux)
4. Si le texte est propre, retourne-le tel quel
5. Réponds UNIQUEMENT avec le texte corrigé, RIEN d'autre

Texte à modérer:
{$text}

Texte censuré: [/INST]
PROMPT;
    }

    /**
     * Extrait le texte censuré de la réponse IA
     */
    private function extractCensoredText(string $aiResponse, string $originalText): string
    {
        $cleaned = trim($aiResponse);
        $cleaned = preg_replace('/```.*?```/s', '', $cleaned);
        $cleaned = trim($cleaned);
        
        if (empty($cleaned) || strlen($cleaned) > strlen($originalText) * 2) {
            return $this->fallbackCensorship($originalText);
        }
        
        return $cleaned;
    }

    /**
     * Fallback: Censure basique si l'IA échoue
     */
    private function fallbackCensorship(string $text): string
    {
        $badWords = [
            'merde', 'putain', 'connard', 'con', 'salaud', 'enculé',
            'pute', 'fdp', 'pd', 'couille', 'bordel', 'enfoiré',
            'kahba', 'ka7ba', 'zebbi', 'zeb', 'nikemek', 'nik', 'kess',
            'fuck', 'shit', 'bitch', 'bastard', 'asshole', 'dick',
            'salope', 'batard', 'putain', 'chier', 'emmerde',
        ];

        $censoredText = $text;
        
        foreach ($badWords as $badWord) {
            $pattern = '/\b' . preg_quote($badWord, '/') . '\b/ui';
            $replacement = str_repeat('*', mb_strlen($badWord));
            $censoredText = preg_replace($pattern, $replacement, $censoredText);
        }
        
        return $censoredText;
    }

    /**
     * Sauvegarde dans la BD
     */
    private function saveCensoredText(
        string $hash, 
        string $original, 
        string $censored, 
        bool $hasBadWords
    ): void {
        $censoredTextEntity = new CensoredText();
        $censoredTextEntity->setTextHash($hash);
        $censoredTextEntity->setOriginalText($original);
        $censoredTextEntity->setCensoredText($censored);
        $censoredTextEntity->setHasBadWords($hasBadWords);

        $this->censoredTextRepository->save($censoredTextEntity);
    }

    /**
     * Vérifie si le texte contient des bad words
     */
    public function hasBadWords(string $text): bool
    {
        if (empty(trim($text))) {
            return false;
        }

        $textHash = md5(trim($text));
        $cached = $this->censoredTextRepository->findByTextHash($textHash);
        
        if ($cached !== null) {
            return $cached->isHasBadWords();
        }

        $this->censorBadWords($text);
        $cached = $this->censoredTextRepository->findByTextHash($textHash);
        return $cached ? $cached->isHasBadWords() : false;
    }

    /**
     * Récupère les statistiques
     */
    public function getStatistics(): array
    {
        return $this->censoredTextRepository->getStatistics();
    }
}
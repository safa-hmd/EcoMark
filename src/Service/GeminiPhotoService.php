<?php
// src/Service/GeminiPhotoService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiPhotoService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private ?LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $client, 
        string $geminiApiKey,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->apiKey = $geminiApiKey;
        $this->logger = $logger;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
        error_log($message);
    }

    /**
     * Modération rapide avec logs détaillés
     */
    public function quickModeration(string $base64Image, string $mimeType = 'image/jpeg'): bool
    {
        $this->log("🤖 [GEMINI] Début modération");
        $this->log("📊 [GEMINI] MimeType: {$mimeType}");
        $this->log("📊 [GEMINI] Taille base64: " . strlen($base64Image) . " caractères");
        
        $prompt = "Cette image est-elle appropriée pour un profil? Réponds SAFE ou UNSAFE.";

        try {
            $url = "{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$this->apiKey}";
            $this->log("🌐 [GEMINI] URL: " . $url);
            
            $this->log("⏳ [GEMINI] Envoi requête...");
            
            $response = $this->client->request('POST', $url, [
                'json' => [
                    'contents' => [[
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]]
                        ]
                    ]]
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $this->log("📡 [GEMINI] Status: {$statusCode}");

            if ($statusCode !== 200) {
                $this->log("❌ [GEMINI] Erreur HTTP {$statusCode}");
                return false;
            }

            $data = $response->toArray();
            $this->log("📦 [GEMINI] Réponse reçue");
            
            $result = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? 'UNSAFE');
            $this->log("✅ [GEMINI] Résultat modération: {$result}");
            
            return strtoupper($result) === 'SAFE';
            
        } catch (\Symfony\Component\HttpClient\Exception\ClientException $e) {
            $this->log("❌ [GEMINI] ClientException: " . $e->getMessage());
            $this->log("📄 [GEMINI] Response body: " . $e->getResponse()->getContent(false));
            throw $e;
        } catch (\Symfony\Component\HttpClient\Exception\ServerException $e) {
            $this->log("❌ [GEMINI] ServerException: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->log("❌ [GEMINI] Exception générale: " . $e->getMessage());
            $this->log("📍 [GEMINI] Trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Analyse complète avec logs
     */
    public function analyzePhoto(string $base64Image, string $mimeType = 'image/jpeg'): array
    {
        $this->log("🔍 [GEMINI] Début analyse photo");
        
        $prompt = "Analyse cette photo de profil et réponds en JSON:
{
  \"moderation\": {\"is_safe\": true/false, \"reason\": \"\"},
  \"quality\": {\"score\": 0-10, \"resolution\": \"low/medium/high\"},
  \"content\": {\"has_face\": true/false, \"type\": \"portrait/autre\"},
  \"suggestions\": [\"suggestion1\", \"suggestion2\"]
}";

        try {
            $this->log("⏳ [GEMINI] Envoi analyse...");
            
            $response = $this->client->request('POST', 
                "{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$this->apiKey}",
                [
                    'json' => [
                        'contents' => [[
                            'parts' => [
                                ['text' => $prompt],
                                ['inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image
                                ]]
                            ]
                        ]]
                    ],
                    'timeout' => 30
                ]
            );

            $statusCode = $response->getStatusCode();
            $this->log("📡 [GEMINI] Analyse status: {$statusCode}");

            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            $this->log("📝 [GEMINI] Texte reçu: " . substr($text, 0, 200) . "...");
            
            $text = preg_replace('/```json\s*|\s*```/', '', trim($text));
            $analysis = json_decode($text, true);
            
            if (!$analysis) {
                $this->log("⚠️ [GEMINI] JSON invalide, utilisation valeurs par défaut");
                return $this->getDefaultAnalysis();
            }
            
            $this->log("✅ [GEMINI] Analyse terminée");
            return $analysis;
            
        } catch (\Exception $e) {
            $this->log("❌ [GEMINI] Erreur analyse: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Génère un prompt optimisé
     */
    public function generateAvatarPrompt(string $userDescription): string
    {
        $this->log("🎨 [GEMINI] Génération prompt pour: {$userDescription}");
        
        $prompt = "Transforme cette description simple en un prompt détaillé pour générer un avatar professionnel.

Description utilisateur: \"{$userDescription}\"

Crée un prompt en anglais qui inclut:
- Style: professionnel, portrait, avatar
- Qualité: high quality, detailed, photorealistic
- Composition: centered, professional headshot
- Background: simple, clean, professional
- Lighting: studio lighting

Réponds UNIQUEMENT avec le prompt optimisé en anglais, sans explication.";

        try {
            $response = $this->client->request('POST',
                "{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$this->apiKey}",
                [
                    'json' => [
                        'contents' => [[
                            'parts' => [['text' => $prompt]]
                        ]]
                    ],
                    'timeout' => 15
                ]
            );

            $data = $response->toArray();
            $optimizedPrompt = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? $userDescription);
            
            $this->log("✅ [GEMINI] Prompt généré: {$optimizedPrompt}");
            
            return $optimizedPrompt;
        } catch (\Exception $e) {
            $this->log("❌ [GEMINI] Erreur génération prompt: " . $e->getMessage());
            return "professional headshot portrait of {$userDescription}, high quality, detailed, studio lighting, clean background";
        }
    }

    /**
     * Améliore la description d'une image
     */
    public function enhanceImageDescription(string $base64Image, string $mimeType = 'image/jpeg'): string
    {
        $this->log("✨ [GEMINI] Enhancement description image");
        
        $prompt = "Analyse cette photo et génère une description détaillée pour recréer une version améliorée.

Inclus:
- Ce que tu vois
- Le style et l'ambiance
- Les couleurs dominantes
- Suggestions d'amélioration

Réponds avec un prompt en anglais pour générer une version améliorée et professionnelle.";

        try {
            $response = $this->client->request('POST',
                "{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$this->apiKey}",
                [
                    'json' => [
                        'contents' => [[
                            'parts' => [
                                ['text' => $prompt],
                                ['inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image
                                ]]
                            ]
                        ]]
                    ],
                    'timeout' => 30
                ]
            );

            $data = $response->toArray();
            $enhanced = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? 'professional portrait photo, high quality');
            
            $this->log("✅ [GEMINI] Description améliorée");
            
            return $enhanced;
        } catch (\Exception $e) {
            $this->log("❌ [GEMINI] Erreur enhancement: " . $e->getMessage());
            return 'professional portrait photo, high quality, detailed';
        }
    }

    private function getDefaultAnalysis(): array
    {
        return [
            'moderation' => ['is_safe' => true, 'reason' => ''],
            'quality' => ['score' => 5, 'resolution' => 'medium'],
            'content' => ['has_face' => true, 'type' => 'portrait'],
            'suggestions' => ['Aucune suggestion disponible']
        ];
    }
}
<?php
// src/Service/PollinationsImageService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PollinationsImageService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Génère une image via Pollinations - VERSION ULTRA ROBUSTE
     */
    public function generateImage(string $prompt): array
    {
        try {
            // Nettoyer et optimiser le prompt
            $cleanPrompt = $this->optimizePrompt($prompt);
            
            // Construire l'URL avec paramètres optimaux
            $seed = rand(1000, 999999);
            $url = "https://image.pollinations.ai/prompt/{$cleanPrompt}";
            $url .= "?width=512&height=512&nologo=true&seed={$seed}&enhance=true";
            
            // Tentative de génération avec retry
            $result = $this->generateWithRetry($url, $cleanPrompt);
            
            if ($result['success']) {
                return $result;
            }
            
            // Fallback: Générer une image placeholder professionnelle
            return $this->generatePlaceholder($prompt);
            
        } catch (\Exception $e) {
            error_log("Pollinations Error: " . $e->getMessage());
            return $this->generatePlaceholder($prompt);
        }
    }

    /**
     * Génère avec plusieurs tentatives
     */
    private function generateWithRetry(string $baseUrl, string $prompt): array
    {
        $attempts = 3;
        
        for ($i = 0; $i < $attempts; $i++) {
            try {
                // Ajouter un délai entre les tentatives
                if ($i > 0) {
                    sleep(2);
                }
                
                // Générer un nouveau seed à chaque tentative
                $newSeed = rand(1000 + ($i * 1000), 999999);
                
                // Remplacer ou ajouter le seed dans l'URL
                if (strpos($baseUrl, 'seed=') !== false) {
                    $url = preg_replace('/seed=\d+/', "seed={$newSeed}", $baseUrl);
                } else {
                    $url = $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . "seed={$newSeed}";
                }
                
                $response = $this->client->request('GET', $url, [
                    'timeout' => 30,
                    'max_redirects' => 5,
                    'headers' => [
                        'Accept' => 'image/png,image/jpeg,image/*',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ]
                ]);

                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 200) {
                    $imageData = $response->getContent(false);
                    
                    // Vérifier que c'est une vraie image (> 1KB)
                    if (strlen($imageData) > 1024) {
                        return [
                            'success' => true,
                            'image_data' => $imageData,
                            'image_base64' => 'data:image/png;base64,' . base64_encode($imageData),
                            'prompt_used' => $prompt,
                            'source' => 'pollinations'
                        ];
                    }
                }
                
            } catch (\Exception $e) {
                error_log("Tentative {$i} échouée: " . $e->getMessage());
                continue;
            }
        }
        
        return ['success' => false];
    }

    /**
     * Optimise le prompt pour Pollinations
     */
    private function optimizePrompt(string $prompt): string
    {
        // Traductions simples FR -> EN
        $translations = [
            'femme' => 'woman',
            'homme' => 'man',
            "d'affaires" => 'business',
            'affaires' => 'business',
            'corporate' => 'corporate',
            'professionnel' => 'professional',
            'professionnelle' => 'professional',
            'portrait' => 'portrait',
            'photo' => 'photo',
            'linkedin' => 'linkedin',
            'profil' => 'profile',
            'avatar' => 'avatar',
            'style' => 'style',
            'fond' => 'background',
            'neutre' => 'neutral'
        ];
        
        $prompt = strtolower(trim($prompt));
        
        // Appliquer les traductions
        foreach ($translations as $fr => $en) {
            $prompt = str_replace($fr, $en, $prompt);
        }
        
        // Garder seulement les mots importants
        $words = explode(' ', $prompt);
        $keywords = ['professional', 'portrait', 'business', 'woman', 'man', 
                     'corporate', 'headshot', 'profile', 'photo', 'avatar', 
                     'linkedin', 'style', 'background', 'neutral'];
        
        $filteredWords = array_filter($words, function($word) use ($keywords) {
            return in_array($word, $keywords) || strlen($word) > 3;
        });
        
        // Limiter à 5-6 mots max
        $finalWords = array_slice($filteredWords, 0, 6);
        
        // Ajouter "professional portrait" si pas présent
        if (!in_array('professional', $finalWords) && !in_array('portrait', $finalWords)) {
            array_unshift($finalWords, 'professional', 'portrait');
        }
        
        // Utiliser rawurlencode pour le chemin de l'URL (encode les espaces en %20)
        return rawurlencode(implode(' ', $finalWords));
    }

    /**
     * Génère une image placeholder professionnelle
     * Compatible PHP 8+ (plus besoin d'imagedestroy)
     */
    private function generatePlaceholder(string $prompt): array
    {
        try {
            $width = 512;
            $height = 512;
            
            // Créer l'image
            $image = imagecreatetruecolor($width, $height);
            
            if ($image === false) {
                throw new \Exception('Impossible de créer l\'image GD');
            }
            
            // Couleurs professionnelles
            $bgColor = imagecolorallocate($image, 45, 139, 139);
            $accentColor = imagecolorallocate($image, 26, 107, 107);
            $textColor = imagecolorallocate($image, 255, 255, 255);
            
            if ($bgColor === false || $accentColor === false || $textColor === false) {
                throw new \Exception('Erreur allocation couleurs');
            }
            
            // Fond dégradé simple
            for ($y = 0; $y < $height; $y++) {
                $ratio = $y / $height;
                $color = imagecolorallocate(
                    $image,
                    (int)(45 + $ratio * 10),
                    (int)(139 - $ratio * 20),
                    (int)(139 - $ratio * 20)
                );
                if ($color !== false) {
                    imageline($image, 0, $y, $width, $y, $color);
                }
            }
            
            // Cercle central
            $centerX = (int)($width / 2);
            $centerY = (int)($height / 2);
            $radius = 120;
            
            imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $accentColor);
            imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $textColor);
            
            // Icône avatar simple (tête + épaules)
            $headRadius = 35;
            imagefilledellipse($image, $centerX, $centerY - 20, $headRadius * 2, $headRadius * 2, $textColor);
            
            // Épaules
            imagefilledarc($image, $centerX, $centerY + 30, 90, 60, 0, 180, $textColor, IMG_ARC_PIE);
            
            // Texte "AI Generated"
            $text = "AI Generated";
            imagestring($image, 3, $centerX - 42, $centerY + 70, $text, $textColor);
            
            // Convertir en PNG
            ob_start();
            imagepng($image, null, 9);
            $imageData = ob_get_clean();
            
            // ✅ PHP 8+: Plus besoin d'imagedestroy(), l'objet GdImage est automatiquement libéré
            // L'image sera détruite automatiquement à la fin de la fonction
            
            if ($imageData === false) {
                throw new \Exception('Erreur lors de la génération PNG');
            }
            
            return [
                'success' => true,
                'image_data' => $imageData,
                'image_base64' => 'data:image/png;base64,' . base64_encode($imageData),
                'prompt_used' => $prompt,
                'source' => 'placeholder',
                'note' => 'Image générée localement (Pollinations temporairement indisponible)'
            ];
            
        } catch (\Exception $e) {
            error_log('Erreur generatePlaceholder: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Impossible de générer l\'image placeholder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sauvegarde l'image
     */
    public function saveImage(string $imageData, string $filename): bool
    {
        try {
            $uploadDir = __DIR__ . '/../../public/uploads/photos';
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Impossible de créer le dossier: {$uploadDir}");
                    return false;
                }
            }
            
            $filepath = $uploadDir . '/' . $filename;
            $result = file_put_contents($filepath, $imageData);
            
            if ($result === false) {
                error_log("Impossible d'écrire dans: {$filepath}");
                return false;
            }
            
            // Vérifier les permissions
            if (file_exists($filepath)) {
                chmod($filepath, 0644);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Erreur sauvegarde: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère plusieurs variantes
     */
    public function generateVariants(string $prompt, int $count = 3): array
    {
        $variants = [];
        $count = min($count, 3); // Limiter à 3 max
        
        for ($i = 0; $i < $count; $i++) {
            // Ajouter une variation au prompt
            $variantPrompt = $prompt . " variant" . ($i + 1);
            
            $result = $this->generateImage($variantPrompt);
            $result['variant_number'] = $i + 1;
            
            $variants[] = $result;
            
            // Pause entre les générations
            if ($i < $count - 1) {
                sleep(1);
            }
        }
        
        return $variants;
    }
}
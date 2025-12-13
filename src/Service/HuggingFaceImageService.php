<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HuggingFaceImageService
{
    private string $apiKey;
    private HttpClientInterface $client;
    private ParameterBagInterface $params;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->apiKey = $_ENV['HUGGINGFACE_API_KEY'];
        $this->client = $client;
        $this->params = $params;
    }

    public function generateImage(string $prompt): string
    {
        //  Nouvelle URL avec router.huggingface.co
        $response = $this->client->request('POST',
            'https://router.huggingface.co/hf-inference/models/black-forest-labs/FLUX.1-dev',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                ],
                'timeout' => 60,
            ]
        );

        //  L'API renvoie directement l'image en binaire
        $imageData = $response->getContent();
        
        // Sauvegarde de l'image
        $fileName = 'event_' . uniqid() . '.png';
        $uploadPath = $this->params->get('kernel.project_dir') . '/public/uploads/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        file_put_contents($uploadPath . $fileName, $imageData);

        return $fileName;
    }
}
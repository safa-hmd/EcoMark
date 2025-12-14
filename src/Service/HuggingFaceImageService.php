<?php
namespace App\Service;
use Symfony\Contracts\HttpClient\HttpClientInterface;
class HuggingFaceImageService
{
    private HttpClientInterface $client;
    private string $apiKey;
    
    private const MODELS = [
        'realistic' => 'SG161222/Realistic_Vision_V2.0',
        'portrait' => 'dreamlike-art/dreamlike-photoreal-2.0'
    ];

    public function __construct(HttpClientInterface $client, string $huggingfaceApiKey)
    {
        $this->client = $client;
        $this->apiKey = $huggingfaceApiKey;
    }

    public function generateImage(string $prompt, string $modelType = 'realistic'): array
    {
        $model = self::MODELS[$modelType] ?? self::MODELS['realistic'];
        
        try {
            $response = $this->client->request('POST',
                "https://api-inference.huggingface.co/models/{$model}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $prompt,
                        'options' => ['wait_for_model' => true]
                    ],
                    'timeout' => 60
                ]
            );

            $imageData = $response->getContent();
            $base64Image = base64_encode($imageData);
            
            return [
                'success' => true,
                'image_data' => $imageData,
                'image_base64' => 'data:image/jpeg;base64,' . $base64Image
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function saveImage(string $imageData, string $filename): ?string
    {
        try {
            $uploadDir = __DIR__ . '/../../public/uploads/photos';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filepath = $uploadDir . '/' . $filename;
            file_put_contents($filepath, $imageData);
            
            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
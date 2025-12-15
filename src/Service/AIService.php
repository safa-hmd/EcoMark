<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private string $apiKey;
    private HttpClientInterface $client;
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(string $apiKey, HttpClientInterface $client)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    public function ask(string $question, string $context): string
    {
        try {
            $payload = [
                'model' => 'openai/gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un assistant e-commerce. Réponds en français.'],
                    ['role' => 'user', 'content' => "Contexte utilisateur : " . $context],
                    ['role' => 'user', 'content' => $question]
                ]
            ];

            $response = $this->client->request("POST", self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray(false);

            return $data["choices"][0]["message"]["content"]
                ?? "Erreur : aucune réponse du modèle.";

        } catch (\Exception $e) {
            return "Erreur IA : " . $e->getMessage();
        }
    }
}

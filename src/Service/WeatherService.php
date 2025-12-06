<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getWeather(string $city): ?array
    {
        $url = "https://api.openweathermap.org/data/2.5/weather";

        $response = $this->client->request('GET', $url, [
            'query' => [
                'q' => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ]
        ]);

        return $response->toArray();
    }
}

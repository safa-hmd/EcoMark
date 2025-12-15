<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Géocode une adresse avec Nominatim (OpenStreetMap)
     *
     * @param string $address Adresse complète (rue, ville, pays…)
     * @return array|null ['lat' => float, 'lon' => float] ou null si introuvable
     */
    public function geocode(string $address): ?array
    {
        $response = $this->client->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'format' => 'json',
                'q'      => $address,
                'limit'  => 1,
            ],
            // Nominatim OBLIGE un User-Agent explicite
            'headers' => [
                'User-Agent' => 'EcoMark/1.0 (contact@exemple.com)',
            ],
        ]);

        $data = $response->toArray();

        if (empty($data)) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }
}

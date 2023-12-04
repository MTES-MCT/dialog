<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnWfsRoadGeocoder implements RoadGeocoderInterface
{
    public function __construct(
        private string $ignWfsUrl,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function computeRoadLine(string $roadName, string $inseeCode): string
    {
        $query = [
            'SERVICE' => 'WFS',
            'REQUEST' => 'GetFeature',
            'VERSION' => '2.0.0',
            'OUTPUTFORMAT' => 'application/json',
            'TYPENAME' => 'BDTOPO_V3:voie_nommee',
            'cql_filter' => sprintf("nom_minuscule='%s' AND code_insee='%s'", strtolower($roadName), $inseeCode),
            'PropertyName' => 'geometrie',
        ];

        $response = $this->httpClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);

        $body = $response->getContent(throw: false);

        try {
            $data = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        return json_encode($data['features'][0]['geometry']);
    }
}

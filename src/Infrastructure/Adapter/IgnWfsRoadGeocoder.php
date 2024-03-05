<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnWfsRoadGeocoder implements RoadGeocoderInterface
{
    public function __construct(
        private string $ignWfsUrl,
        private HttpClientInterface $ignWfsClient,
    ) {
    }

    public function computeRoadLine(string $roadName, string $inseeCode): string
    {
        $normalizedRoadName = str_replace(["'", '-', '’'], ["''", ' ', "''"], strtolower($roadName));

        $query = [
            'SERVICE' => 'WFS',
            'REQUEST' => 'GetFeature',
            'VERSION' => '2.0.0',
            'OUTPUTFORMAT' => 'application/json',
            'TYPENAME' => 'BDTOPO_V3:voie_nommee',
            'cql_filter' => sprintf("strStripAccents(strReplace(nom_minuscule, '-', ' ', true))=strStripAccents(strReplace('%s', '-', ' ', true)) AND code_insee='%s'", $normalizedRoadName, $inseeCode),
            'PropertyName' => 'geometrie',
        ];

        $response = $this->ignWfsClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);

        try {
            $body = $response->getContent(throw: true);
        } catch (HttpExceptionInterface $exc) {
            $message = sprintf('invalid response: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        try {
            $data = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        // We could have try-catch'd $data['features'][0]['geometry'] (better ask for forgiveness than for permission)
        // but PHP does not raise a proper exception upon key errors.
        // So we have to be defensive with this ugly line of code...
        $geometry = \array_key_exists('features', $data) ? (\array_key_exists(0, $data['features']) ? ($data['features'][0]['geometry'] ?? null) : null) : null;

        if (!\is_null($geometry)) {
            return json_encode($geometry);
        }

        $message = sprintf('could not retrieve geometry for roadName="%s", inseeCode="%s", response was: %s', $normalizedRoadName, $inseeCode, $body);
        throw new GeocodingFailureException($message);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RoadGeocoder implements RoadGeocoderInterface
{
    public function __construct(
        private string $ignWfsUrl,
        private HttpClientInterface $ignWfsClient,
    ) {
    }

    private function fetch(string $typeName, string $cqlFilter, string $propertyName): array
    {
        $response = $this->ignWfsClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'SERVICE' => 'WFS',
                'REQUEST' => 'GetFeature',
                'VERSION' => '2.0.0',
                'OUTPUTFORMAT' => 'application/json',
                'TYPENAME' => $typeName,
                'cql_filter' => $cqlFilter,
                'PropertyName' => $propertyName,
            ],
        ]);
        try {
            $body = $response->getContent(throw: true);
        } catch (\Exception $exc) {
            $message = sprintf('invalid response: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        try {
            return json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\Exception $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }
    }

    public function computeRoadLine(string $roadName, string $inseeCode): string
    {
        $normalizedRoadName = strtolower($roadName);

        $data = $this->fetch(
            typeName: 'BDTOPO_V3:voie_nommee',
            cqlFilter: sprintf("strStripAccents(nom_minuscule)=strStripAccents('%s') AND code_insee='%s'", $normalizedRoadName, $inseeCode),
            propertyName: 'geometrie',
        );

        // We could have try-catch'd $data['features'][0]['geometry'] (better ask for forgiveness than for permission)
        // but PHP does not raise a proper exception upon key errors.
        // So we have to be defensive with this ugly line of code...
        $geometry = \array_key_exists('features', $data) ? (\array_key_exists(0, $data['features']) ? ($data['features'][0]['geometry'] ?? null) : null) : null;

        if (!\is_null($geometry)) {
            return json_encode($geometry);
        }

        $message = sprintf('could not retrieve geometry for roadName="%s", inseeCode="%s", response was: %s', $roadName, $inseeCode, json_encode($data));
        throw new GeocodingFailureException($message);
    }

    public function findDepartmentalRoads(string $search, string $administrator): array
    {
        $normalizedSearch = str_replace("'", "''", strtoupper($search));

        $data = $this->fetch(
            typeName: 'BDTOPO_V3:route_numerotee_ou_nommee',
            cqlFilter: sprintf("strStartsWith(numero, '%s')=true AND gestionnaire='%s' AND type_de_route='Départementale'", $normalizedSearch, $administrator),
            propertyName: 'numero,geometrie',
        );

        $departmentalRoads = [];

        if (isset($data['features'])) {
            foreach ($data['features'] as $feature) {
                $departmentalRoads[] = [
                    'roadNumber' => $feature['properties']['numero'],
                    'geometry' => json_encode($feature['geometry']),
                ];
            }
        }

        return $departmentalRoads;
    }
}

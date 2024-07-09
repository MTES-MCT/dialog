<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class LitteralisExtractor
{
    public function __construct(
        private readonly HttpClientInterface $litteralisWfsHttpClient,
    ) {
    }

    public function extractFeaturesByRegulation(LitteralisReporter $reporter, ?int $limit = null): array
    {
        $cqlFilter = "mesures ILIKE '%circulation interdite%' OR mesures ILIKE '%limitation de vitesse%'";

        // On calcule des totaux qui seront affichés dans le rapport final

        $numTotalFeatures = $this->countFeatures($reporter);
        $reporter->setCount($reporter::COUNT_TOTAL_FEATURES, $numTotalFeatures);

        $numMatchingFeatures = $this->countFeatures($reporter, $cqlFilter);
        $reporter->setCount($reporter::COUNT_MATCHING_FEATURES, $numMatchingFeatures, ['%cqlFilter%' => $cqlFilter]);

        $numExtractedFeatures = 0;

        // L'API Litteralis est paginée. Pour récupérer toutes les données, on fixe un nombre maximum d'emprises
        // par page et on détermine le nombre total de pages à partir du champ GeoJSON 'totalFeatures' qu'on récupèrera
        // dans la réponse à la 1ère requête.
        $numPerPage = 1000;
        $totalPages = INF;

        // On regroupe les emprises par arrêté (arretesrcid).
        $featuresByRegulation = [];

        for ($pageNumber = 1; $pageNumber <= $totalPages; ++$pageNumber) {
            $method = 'GET';
            $path = '/maplink/public/wfs';
            $options = [
                'query' => [
                    'outputFormat' => 'application/json',
                    'SERVICE' => 'wfs',
                    'VERSION' => '2',
                    'REQUEST' => 'GetFeature',
                    'TYPENAME' => 'litteralis:litteralis',
                    'cql_filter' => $cqlFilter,
                    'count' => $numPerPage,
                    'startIndex' => $numPerPage * ($pageNumber - 1),
                ],
            ];

            $response = $this->makeRequest($reporter, $method, $path, $options);
            $geoJSON = json_decode($response->getContent(), true);

            if ($totalPages === INF) {
                // Calcul du nombre total de pages
                $totalPages = intdiv($geoJSON['totalFeatures'], $numPerPage) + 1;
            }

            foreach ($geoJSON['features'] as $feature) {
                $identifier = $feature['properties']['arretesrcid'];

                if (empty($feature['geometry'])) {
                    // Parfois la 'geometry' est absente
                    $reporter->addWarning($reporter::WARNING_MISSING_GEOMETRY, [
                        'idemprise' => $feature['properties']['idemprise'],
                        'arretesrcid' => $identifier,
                    ]);
                    continue;
                }

                // D'après la documentation Litteralis, les coordonnées sont en EPSG:4326.
                // Mais la 'geometry' n'a pas de 'crs' pour l'indiquer explicitement, comme requis par PostGIS.
                // Donc on rajoute le 'crs' nous-mêmes.
                $feature['geometry']['crs'] = [
                    'type' => 'name',
                    'properties' => ['name' => 'EPSG:4326'],
                ];

                $featuresByRegulation[$identifier][] = $feature;
                ++$numExtractedFeatures;
            }
        }

        $reporter->setCount($reporter::COUNT_EXTRACTED_FEATURES, $numExtractedFeatures);
        $reporter->onExtract(json_encode($featuresByRegulation, JSON_UNESCAPED_UNICODE & JSON_UNESCAPED_SLASHES));

        return $featuresByRegulation;
    }

    private function makeRequest(LitteralisReporter $reporter, string $method, string $path, array $options): ResponseInterface
    {
        $reporter->onRequest($method, $path, $options);
        $response = $this->litteralisWfsHttpClient->request($method, $path, $options);
        $reporter->onResponse($response);

        return $response;
    }

    private function countFeatures(LitteralisReporter $reporter, ?string $cqlFilter = null): int
    {
        $method = 'GET';
        $path = '/maplink/public/wfs';
        $options = [
            'query' => [
                'outputFormat' => 'application/json',
                'SERVICE' => 'wfs',
                'VERSION' => '2',
                'REQUEST' => 'GetFeature',
                'TYPENAME' => 'litteralis:litteralis',
                'count' => 1,
                'startIndex' => 0,
            ],
        ];

        if ($cqlFilter) {
            $options['query']['cql_filter'] = $cqlFilter;
        }

        $response = $this->makeRequest($reporter, $method, $path, $options);
        $geoJSON = json_decode($response->getContent(), true);

        return $geoJSON['numberMatched'];
    }
}

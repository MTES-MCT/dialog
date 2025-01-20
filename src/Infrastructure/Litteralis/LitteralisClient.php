<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Infrastructure\IntegrationReport\Reporter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class LitteralisClient
{
    private string $credentials;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function setCredentials(string $credentials): void
    {
        if (!$credentials) {
            throw new \RuntimeException('Credentials are empty');
        }

        $this->credentials = $credentials;
    }

    private function makeRequest(string $method, string $path, array $options, ?Reporter $reporter = null): ResponseInterface
    {
        if (!isset($this->credentials)) {
            throw new \RuntimeException('Credentials not set, call `setCredentials()` first');
        }

        if ($reporter) {
            $reporter->onRequest($method, $path, $options);
        }

        $options['auth_basic'] = $this->credentials;
        $response = $this->httpClient->request($method, $path, $options);

        if ($reporter) {
            $reporter->onResponse($response);
        }

        return $response;
    }

    public function fetchAllPaginated(string $cqlFilter, ?Reporter $reporter = null): array
    {
        $features = [];

        // L'API Litteralis est paginée. Pour récupérer toutes les données, on fixe un nombre maximum d'emprises
        // par page et on détermine le nombre total de pages à partir du champ GeoJSON 'totalFeatures' qu'on récupèrera
        // dans la réponse à la 1ère requête.
        $numPerPage = 1000;
        $totalPages = INF;

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

            $response = $this->makeRequest($method, $path, $options, $reporter);
            $geoJSON = json_decode($response->getContent(), true);

            if ($totalPages === INF) {
                // Calcul du nombre total de pages
                $totalPages = intdiv($geoJSON['totalFeatures'], $numPerPage) + 1;
            }

            foreach ($geoJSON['features'] as $feature) {
                $features[] = $feature;
            }
        }

        return $features;
    }

    public function count(?string $cqlFilter = null, ?Reporter $reporter = null): int
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

        $response = $this->makeRequest($method, $path, $options, $reporter);
        $geoJSON = json_decode($response->getContent(), true);

        return $geoJSON['numberMatched'];
    }

    public function fetchAllByRegulationId(string $id): array
    {
        $cqlFilter = \sprintf("arretesrcid = '%s'", $id);

        return $this->fetchAllPaginated($cqlFilter);
    }
}

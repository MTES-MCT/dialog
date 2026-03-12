<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Litteralis;

use App\Infrastructure\Integration\IntegrationReport\Reporter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Client WFS Litteralis (couche configurable).
 * Utilisé pour le flux standard (litteralis:litteralis) et le flux Communication (litteralis:LIcommunication).
 */
final class LitteralisClient
{
    public const TYPENAME_STANDARD = 'litteralis:litteralis';
    public const TYPENAME_COMMUNICATION = 'litteralis:LIcommunication';

    private string $credentials;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $typename,
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
        $numPerPage = 500;
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
                    'TYPENAME' => $this->typename,
                    'cql_filter' => $cqlFilter,
                    'count' => $numPerPage,
                    'startIndex' => $numPerPage * ($pageNumber - 1),
                ],
            ];

            $response = $this->makeRequest($method, $path, $options, $reporter);
            $geoJSON = json_decode($response->getContent(), true);

            if ($totalPages === INF) {
                $totalPages = (int) ceil(($geoJSON['totalFeatures'] ?? 0) / $numPerPage);
                if ($totalPages < 1) {
                    $totalPages = 1;
                }
            }

            foreach ($geoJSON['features'] ?? [] as $feature) {
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
                'TYPENAME' => $this->typename,
                'count' => 1,
                'startIndex' => 0,
            ],
        ];

        if ($cqlFilter) {
            $options['query']['cql_filter'] = $cqlFilter;
        }

        $response = $this->makeRequest($method, $path, $options, $reporter);
        $geoJSON = json_decode($response->getContent(), true);

        return (int) ($geoJSON['numberMatched'] ?? 0);
    }

    public function fetchAllByRegulationId(string $id): array
    {
        $cqlFilter = \sprintf("arretesrcid = '%s'", $id);

        return $this->fetchAllPaginated($cqlFilter);
    }
}

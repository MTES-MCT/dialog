<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Domain\User\Exception\OrganizationNotFoundException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ApiOrganizationFetcherMock extends MockHttpClient
{
    private string $baseUri = 'https://testserver';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $payload = match ($options['query']['q']) {
            // Commune
            '22930008201453' => [
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'COMMUNE DE SAVENAY',
                        'nature_juridique' => '7210',
                        'complements' => [
                            'collectivite_territoriale' => [
                                'code_insee' => '44195',
                            ],
                        ],
                    ],
                ],
            ],
            // Département
            '22930008201453' => [
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'DEPARTEMENT DE LA SEINE SAINT DENIS',
                        'nature_juridique' => '7220',
                        'siege' => [
                            'departement' => '93',
                        ],
                    ],
                ],
            ],
            // EPCI
            '20005478100022' => [
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'METROPOLE DU GRAND PARIS (MGP)',
                        'nature_juridique' => '7344',
                        'siege' => [
                            'epci' => '200054781',
                        ],
                    ],
                ],
            ],
            // Région
            '23750007900312' => [
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'REGION ILE DE FRANCE',
                        'nature_juridique' => '7230',
                        'siege' => [
                            'region' => '11',
                        ],
                    ],
                ],
            ],
            default => throw new OrganizationNotFoundException(),
        };

        return new MockResponse(
            json_encode($payload, JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\ApiOrganizationFetcherInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiOrganizationFetcher implements ApiOrganizationFetcherInterface
{
    public function __construct(
        private HttpClientInterface $organizationFetcherClient,
    ) {
    }

    public function findBySiret(string $siret): array
    {
        $response = $this->organizationFetcherClient->request(
            'GET',
            'search',
            [
                'query' => [
                    'q' => $siret,
                    'est_collectivite_territoriale' => 'true',
                ],
            ],
        );

        $data = $response->toArray();

        if (0 === $data['total_results']) {
            throw new OrganizationNotFoundException($siret);
        }

        return [
            'name' => $data['results'][0]['nom_complet'],
        ];
    }
}

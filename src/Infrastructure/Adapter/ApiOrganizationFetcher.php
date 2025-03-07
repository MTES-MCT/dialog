<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Application\OrganizationAdministrativeBoundariesGeometryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiOrganizationFetcher implements ApiOrganizationFetcherInterface
{
    public function __construct(
        private HttpClientInterface $organizationFetcherClient,
        private OrganizationAdministrativeBoundariesGeometryInterface $administrativeBoundariesGeometry,
    ) {
    }

    public function findBySiret(string $siret): OrganizationFetchedView
    {
        $response = $this->organizationFetcherClient->request(
            'GET',
            'search',
            [
                'query' => [
                    'q' => $siret,
                    'est_collectivite_territoriale' => 'true',
                    'include' => 'siege',
                    'minimal' => 'true',
                ],
            ],
        );
        $data = $response->toArray();

        if (0 === $data['total_results']) {
            throw new OrganizationNotFoundException($siret);
        }

        ['codeType' => $codeType, 'code' => $code] = $this->getOrganizationCodes($data['results'][0]);

        $geometry = $this->administrativeBoundariesGeometry->findByCodes($code, $codeType);

        return new OrganizationFetchedView(
            name: $data['results'][0]['nom_complet'],
            codeType: $codeType,
            code: $code,
            geometry: $geometry,
        );
    }

    private function getOrganizationCodes(array $result): array
    {
        // Voir https://id.eaufrance.fr/nsa/606

        return match ($result['nature_juridique']) {
            '7210' => [
                'code' => $result['siege']['code_postal'],
                'codeType' => OrganizationCodeTypeEnum::INSEE->value,
                'search' => \sprintf('communes?codePostal=%s&fields=contour', $result['siege']['code_postal']),
            ],
            '7220' => [
                'code' => $result['siege']['departement'],
                'codeType' => OrganizationCodeTypeEnum::DEPARTMENT->value,
                'search' => \sprintf('communes?codeDepartement=%s&fields=contour', $result['siege']['departement']),
            ],
            '7230' => [
                'code' => $result['siege']['region'],
                'codeType' => OrganizationCodeTypeEnum::REGION->value,
                'search' => \sprintf('communes?codeRegion=%s&fields=contour', $result['siege']['region']),
            ],
            '7343', '7344', '7346', '7347', '7345' => [
                'code' => $result['siege']['epci'],
                'codeType' => OrganizationCodeTypeEnum::EPCI->value,
                'search' => \sprintf('epcis?codeEpci=%s&fields=contour', $result['siege']['epci']),
            ],
            default => throw new \LogicException(\sprintf('Organization not managed: %s', $result['nom_complet'])),
        };
    }
}

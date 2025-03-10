<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Application\OrganizationAdministrativeBoundariesGeometryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiOrganizationFetcher implements ApiOrganizationFetcherInterface
{
    public function __construct(
        private HttpClientInterface $organizationFetcherClient,
        private OrganizationAdministrativeBoundariesGeometryInterface $administrativeBoundariesGeometry,
        private LoggerInterface $logger,
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

        $organizationName = $data['results'][0]['nom_complet'];

        try {
            ['codeType' => $codeType, 'code' => $code] = $this->getOrganizationCodes($data['results'][0]);
            $geometry = $this->administrativeBoundariesGeometry->findByCodes($code, $codeType);

            return new OrganizationFetchedView(
                name: $organizationName,
                codeType: $codeType,
                code: $code,
                geometry: $geometry,
            );
        } catch (\Exception $e) {
            // Dans le cas où la récupération de la géométrie échoue, on retourne uniquement le nom de l'organisation

            $this->logger->warning('Impossible to get organization geometry', [
                'siret' => $siret,
                'name' => $organizationName,
                'nature_juridique' => $data['results'][0]['nature_juridique'],
            ]);

            return new OrganizationFetchedView($organizationName);
        }
    }

    private function getOrganizationCodes(array $result): array
    {
        // Voir https://id.eaufrance.fr/nsa/606

        return match ($result['nature_juridique']) {
            '7210', // Commune et commune nouvelle
            '7179' // Service déconcentré de l'État à compétence territoriale (ex: Préfecture de Police)
            => [
                'code' => $result['siege']['code_postal'],
                'codeType' => OrganizationCodeTypeEnum::INSEE->value,
                'search' => \sprintf('communes?codePostal=%s&fields=contour', $result['siege']['code_postal']),
            ],
            // Département
            '7220' => [
                'code' => $result['siege']['departement'],
                'codeType' => OrganizationCodeTypeEnum::DEPARTMENT->value,
                'search' => \sprintf('communes?codeDepartement=%s&fields=contour', $result['siege']['departement']),
            ],
            // Région
            '7230' => [
                'code' => $result['siege']['region'],
                'codeType' => OrganizationCodeTypeEnum::REGION->value,
                'search' => \sprintf('communes?codeRegion=%s&fields=contour', $result['siege']['region']),
            ],
            // EPCI
            '7343', // Communauté urbaine
            '7344', // Métropole
            '7346', // Communauté de communes
            '7347', // Communauté de villes
            '7345', // Syndicat intercommunal à vocation multiple (SIVOM)
            => [
                'code' => $result['siege']['epci'],
                'codeType' => OrganizationCodeTypeEnum::EPCI->value,
                'search' => \sprintf('epcis?codeEpci=%s&fields=contour', $result['siege']['epci']),
            ],
            default => throw new \LogicException(\sprintf('Organization not managed: %s', $result['nom_complet'])),
        };
    }
}

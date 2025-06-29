<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiOrganizationFetcher implements ApiOrganizationFetcherInterface
{
    private const NATURES_COMMUNE = [
        '7210', // Commune et commune nouvelle
        '7229', // (Autre) Collectivité territoriale
        '7179', // (Autre) Service déconcentré de l'État à compétence territoriale
    ];

    private const NATURES_DEPARTEMENT = [
        '7220', // Département
        '7379', // (Autre) Établissement public administratif local
        '7172', // Service déconcentré de l'État à compétence (inter) départementale
        '7372', // Service départemental d'incendie et de secours (SDIS)
        '7225', // Collectivité et territoire d'Outre Mer
    ];

    private const NATURES_REGION = [
        '7230', // Région
        '7171', // Service déconcentré de l'État à compétence (inter) régionale
    ];

    private const NATURES_EPCI = [
        '7343', // Communauté urbaine
        '7344', // Métropole
        '7346', // Communauté de communes
        '7347', // Communauté de villes
        '7345', // Syndicat intercommunal à vocation multiple (SIVOM)
        '7348', // Communauté d'agglomération
    ];

    public function __construct(
        private HttpClientInterface $organizationFetcherClient,
        private HttpClientInterface $geoApiClient,
    ) {
    }

    public function findBySiret(string $siret): OrganizationFetchedView
    {
        $query = [
            'q' => $siret,
            'est_collectivite_territoriale' => 'true',
            'include' => 'siege,complements',
            'minimal' => 'true',
        ];

        $response = $this->organizationFetcherClient->request('GET', 'search', ['query' => $query]);
        $data = $response->toArray();

        if (0 === $data['total_results']) {
            throw new OrganizationNotFoundException(\sprintf('Organization not found: %s', $siret));
        }

        $result = $data['results'][0];
        ['codeType' => $codeType, 'code' => $code] = $this->getOrganizationCodes($result);

        $departmentName = null;
        $departmentCode = null;

        // Récupération des code/nom du département dans le cas d'une commune ou d'un département
        if (OrganizationCodeTypeEnum::INSEE->value === $codeType || OrganizationCodeTypeEnum::DEPARTMENT->value === $codeType) {
            $geoResponse = $this->geoApiClient->request('GET', 'departements/' . $result['siege']['departement']);
            $geoData = $geoResponse->toArray();

            if (!empty($geoData)) {
                $departmentName = $geoData['nom'];
                $departmentCode = $geoData['code'];
            }
        }

        $siege = $result['siege'];

        return new OrganizationFetchedView(
            name: $result['nom_complet'],
            code: $code,
            codeType: $codeType,
            departmentName: $departmentName,
            departmentCode: $departmentCode,
            establishmentAddress: \sprintf('%s %s %s', $siege['numero_voie'], $siege['type_voie'], $siege['libelle_voie']),
            establishmentZipCode: $siege['code_postal'],
            establishmentCity: $siege['libelle_commune'],
            establishmentAddressComplement: $siege['complement_adresse'] ?? null,
        );
    }

    /**
     * Détermine les codes d'organisation en fonction de sa nature juridique.
     *
     * @see https://id.eaufrance.fr/nsa/606
     */
    private function getOrganizationCodes(array $result): array
    {
        $natureJuridique = $result['nature_juridique'];

        if (\in_array($natureJuridique, self::NATURES_COMMUNE, true)) {
            return [
                'code' => $result['complements']['collectivite_territoriale']['code_insee'] ?? $result['siege']['commune'],
                'codeType' => OrganizationCodeTypeEnum::INSEE->value,
            ];
        }

        if (\in_array($natureJuridique, self::NATURES_DEPARTEMENT, true)) {
            return [
                'code' => $result['siege']['departement'],
                'codeType' => OrganizationCodeTypeEnum::DEPARTMENT->value,
            ];
        }

        if (\in_array($natureJuridique, self::NATURES_REGION, true)) {
            return [
                'code' => $result['siege']['region'],
                'codeType' => OrganizationCodeTypeEnum::REGION->value,
            ];
        }

        if (\in_array($natureJuridique, self::NATURES_EPCI, true)) {
            return [
                'code' => $result['siege']['epci'],
                'codeType' => OrganizationCodeTypeEnum::EPCI->value,
            ];
        }

        throw new OrganizationNotFoundException();
    }
}

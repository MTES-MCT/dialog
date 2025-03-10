<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Infrastructure\Adapter\ApiOrganizationFetcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiOrganizationFetcherTest extends KernelTestCase
{
    private ApiOrganizationFetcher $apiOrganizationFetcher;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->apiOrganizationFetcher = $container->get(ApiOrganizationFetcher::class);
    }

    /**
     * @dataProvider provideSirets
     */
    public function testFindBySiret(string $siret, string $expectedName, string $expectedCodeType): void
    {
        // Ce test est marqué comme incomplet car il nécessite une connexion à une API externe
        // et peut être instable en fonction de la disponibilité de l'API
        $this->markTestIncomplete(
            "Ce test nécessite une connexion à l'API d'organisations et peut être instable.",
        );

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals($expectedName, $result->name);
        $this->assertEquals($expectedCodeType, $result->codeType);

        // Vérification que le résultat est un GeoJSON valide
        $geoJson = json_decode($result->geometry, true);
        $this->assertIsArray($geoJson);
        $this->assertArrayHasKey('type', $geoJson);
        $this->assertArrayHasKey('coordinates', $geoJson);
    }

    public function provideSirets(): array
    {
        return [
            'commune' => [
                'siret' => '21440195200129',
                'expectedName' => 'COMMUNE DE SAVENAY',
                'expectedCodeType' => OrganizationCodeTypeEnum::INSEE->value,
            ],
            'departement' => [
                'siret' => '22930008201453',
                'expectedName' => 'DEPARTEMENT DE LA SEINE SAINT DENIS',
                'expectedCodeType' => OrganizationCodeTypeEnum::DEPARTMENT->value,
            ],
            'region' => [
                'siret' => '23750007900312',
                'expectedName' => 'REGION ILE DE FRANCE',
                'expectedCodeType' => OrganizationCodeTypeEnum::REGION->value,
            ],
            'epci' => [
                'siret' => '20005478100022',
                'expectedName' => 'METROPOLE DU GRAND PARIS',
                'expectedCodeType' => OrganizationCodeTypeEnum::EPCI->value,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\Adapter\ApiOrganizationFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiOrganizationFetcherTest extends TestCase
{
    private MockObject $organizationFetcherClient;
    private MockObject $geoApiClient;
    private ApiOrganizationFetcher $apiOrganizationFetcher;

    protected function setUp(): void
    {
        $this->organizationFetcherClient = $this->createMock(HttpClientInterface::class);
        $this->geoApiClient = $this->createMock(HttpClientInterface::class);

        $this->apiOrganizationFetcher = new ApiOrganizationFetcher(
            $this->organizationFetcherClient,
            $this->geoApiClient,
        );
    }

    public function testFindBySiretForCommune(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
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
                        'siege' => [
                            'departement' => '44',
                            'numero_voie' => '7',
                            'libelle_voie' => 'Rue de la République',
                            'complement_adresse' => 'Bâtiment A',
                            'code_postal' => '44195',
                            'libelle_commune' => 'Savenay',
                        ],
                    ],
                ],
            ]);

        $mockGeoResponse = $this->createMock(ResponseInterface::class);
        $mockGeoResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'nom' => 'Loire-Atlantique',
                'code' => '44',
            ]);

        $this->geoApiClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockGeoResponse);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('22930008201453');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('COMMUNE DE SAVENAY', $result->name);
        $this->assertEquals('44195', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::INSEE->value, $result->codeType);
        $this->assertEquals('Loire-Atlantique', $result->departmentName);
        $this->assertEquals('44', $result->departmentCode);
        $this->assertEquals('7 Rue de la République', $result->establishmentAddress);
        $this->assertEquals('44195', $result->establishmentZipCode);
        $this->assertEquals('Savenay', $result->establishmentCity);
        $this->assertEquals('Bâtiment A', $result->establishmentAddressComplement);
    }

    public function testFindBySiretForDepartment(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'DEPARTEMENT DE LA SEINE SAINT DENIS',
                        'nature_juridique' => '7220',
                        'siege' => [
                            'departement' => '93',
                            'numero_voie' => '7',
                            'libelle_voie' => 'Rue de la République',
                            'complement_adresse' => 'Bâtiment A',
                            'code_postal' => '93406',
                            'libelle_commune' => 'Saint-Ouen-sur-Seine',
                        ],
                    ],
                ],
            ]);

        $mockGeoResponse = $this->createMock(ResponseInterface::class);
        $mockGeoResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'nom' => 'Seine-Saint-Denis',
                'code' => '93',
            ]);

        $this->geoApiClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockGeoResponse);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('22930008201453');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('DEPARTEMENT DE LA SEINE SAINT DENIS', $result->name);
        $this->assertEquals('93', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::DEPARTMENT->value, $result->codeType);
        $this->assertEquals('Seine-Saint-Denis', $result->departmentName);
        $this->assertEquals('93', $result->departmentCode);
        $this->assertEquals('7 Rue de la République', $result->establishmentAddress);
        $this->assertEquals('93406', $result->establishmentZipCode);
        $this->assertEquals('Saint-Ouen-sur-Seine', $result->establishmentCity);
        $this->assertEquals('Bâtiment A', $result->establishmentAddressComplement);
    }

    public function testFindBySiretForRegion(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'REGION ILE DE FRANCE',
                        'nature_juridique' => '7230',
                        'siege' => [
                            'region' => '11',
                            'numero_voie' => '7',
                            'libelle_voie' => 'Rue de la République',
                            'complement_adresse' => 'Bâtiment A',
                            'code_postal' => '93406',
                            'libelle_commune' => 'Saint-Ouen-sur-Seine',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->geoApiClient
            ->expects($this->never())
            ->method('request');

        $result = $this->apiOrganizationFetcher->findBySiret('23750007900312');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('REGION ILE DE FRANCE', $result->name);
        $this->assertEquals('11', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::REGION->value, $result->codeType);
        $this->assertEquals('7 Rue de la République', $result->establishmentAddress);
        $this->assertEquals('93406', $result->establishmentZipCode);
        $this->assertEquals('Saint-Ouen-sur-Seine', $result->establishmentCity);
        $this->assertEquals('Bâtiment A', $result->establishmentAddressComplement);
    }

    public function testFindBySiretForEpci(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'METROPOLE DU GRAND PARIS',
                        'nature_juridique' => '7343',
                        'siege' => [
                            'epci' => '200054781',
                            'numero_voie' => '7',
                            'libelle_voie' => 'Rue de la République',
                            'complement_adresse' => 'Bâtiment A',
                            'code_postal' => '93406',
                            'libelle_commune' => 'Saint-Ouen-sur-Seine',
                        ],
                    ],
                ],
            ]);

        $this->geoApiClient
            ->expects($this->never())
            ->method('request');

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('20005478100022');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('METROPOLE DU GRAND PARIS', $result->name);
        $this->assertEquals('200054781', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::EPCI->value, $result->codeType);
        $this->assertEquals('7 Rue de la République', $result->establishmentAddress);
        $this->assertEquals('93406', $result->establishmentZipCode);
        $this->assertEquals('Saint-Ouen-sur-Seine', $result->establishmentCity);
        $this->assertEquals('Bâtiment A', $result->establishmentAddressComplement);
    }

    public function testFindBySiretNotFound(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 0,
                'results' => [],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->geoApiClient
            ->expects($this->never())
            ->method('request');

        $this->expectException(OrganizationNotFoundException::class);
        $this->apiOrganizationFetcher->findBySiret('12345678901234');
    }

    public function testFindBySiretUnsupportedOrganizationType(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->geoApiClient
            ->expects($this->never())
            ->method('request');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'ORGANISATION NON SUPPORTÉE',
                        'nature_juridique' => '9999',
                        'siege' => [],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->apiOrganizationFetcher->findBySiret('98765432101234');
    }
}

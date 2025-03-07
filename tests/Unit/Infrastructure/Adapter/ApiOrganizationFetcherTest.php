<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Organization\View\OrganizationFetchedView;
use App\Application\OrganizationAdministrativeBoundariesGeometryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\Adapter\ApiOrganizationFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiOrganizationFetcherTest extends TestCase
{
    private MockObject $organizationFetcherClient;
    private MockObject $administrativeBoundariesGeometry;
    private MockObject $logger;
    private ApiOrganizationFetcher $apiOrganizationFetcher;

    protected function setUp(): void
    {
        $this->organizationFetcherClient = $this->createMock(HttpClientInterface::class);
        $this->administrativeBoundariesGeometry = $this->createMock(OrganizationAdministrativeBoundariesGeometryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->apiOrganizationFetcher = new ApiOrganizationFetcher(
            $this->organizationFetcherClient,
            $this->administrativeBoundariesGeometry,
            $this->logger,
        );
    }

    public function testFindBySiretForCommune(): void
    {
        $siret = '21440195200129'; // COMMUNE DE SAVENAY
        $expectedGeometry = '{"type":"Polygon","coordinates":[[[-1.908121,47.283032],[-1.979851,47.293559]]]}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'COMMUNE DE SAVENAY',
                        'nature_juridique' => '7210', // Commune
                        'siege' => [
                            'code_postal' => '44260',
                            'departement' => '44',
                            'region' => '52',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->with(
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
            )
            ->willReturn($mockResponse);

        $this->administrativeBoundariesGeometry
            ->expects($this->once())
            ->method('findByCodes')
            ->with('44260', OrganizationCodeTypeEnum::INSEE->value)
            ->willReturn($expectedGeometry);

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('COMMUNE DE SAVENAY', $result->name);
        $this->assertEquals('44260', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::INSEE->value, $result->codeType);
        $this->assertEquals($expectedGeometry, $result->geometry);
    }

    public function testFindBySiretForDepartment(): void
    {
        $siret = '22930008201453'; // DEPARTEMENT DE LA SEINE SAINT DENIS
        $expectedGeometry = '{"type":"Polygon","coordinates":[[[2.415986,48.85136],[2.412453,48.876443]]]}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'DEPARTEMENT DE LA SEINE SAINT DENIS',
                        'nature_juridique' => '7220', // Département
                        'siege' => [
                            'code_postal' => '93000',
                            'departement' => '93',
                            'region' => '11',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->administrativeBoundariesGeometry
            ->expects($this->once())
            ->method('findByCodes')
            ->with('93', OrganizationCodeTypeEnum::DEPARTMENT->value)
            ->willReturn($expectedGeometry);

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('DEPARTEMENT DE LA SEINE SAINT DENIS', $result->name);
        $this->assertEquals('93', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::DEPARTMENT->value, $result->codeType);
        $this->assertEquals($expectedGeometry, $result->geometry);
    }

    public function testFindBySiretForRegion(): void
    {
        $siret = '23750007900312'; // REGION ILE DE FRANCE
        $expectedGeometry = '{"type":"Polygon","coordinates":[[[1.709034,48.59525],[1.714035,48.598107]]]}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'REGION ILE DE FRANCE',
                        'nature_juridique' => '7230', // Région
                        'siege' => [
                            'code_postal' => '75000',
                            'departement' => '75',
                            'region' => '11',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->administrativeBoundariesGeometry
            ->expects($this->once())
            ->method('findByCodes')
            ->with('11', OrganizationCodeTypeEnum::REGION->value)
            ->willReturn($expectedGeometry);

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('REGION ILE DE FRANCE', $result->name);
        $this->assertEquals('11', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::REGION->value, $result->codeType);
        $this->assertEquals($expectedGeometry, $result->geometry);
    }

    public function testFindBySiretForEpci(): void
    {
        $siret = '20005478100022'; // METROPOLE DU GRAND PARIS
        $expectedGeometry = '{"type":"Polygon","coordinates":[[[2.320466,48.746817],[2.314344,48.743296]]]}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'METROPOLE DU GRAND PARIS',
                        'nature_juridique' => '7343', // EPCI
                        'siege' => [
                            'code_postal' => '75000',
                            'departement' => '75',
                            'region' => '11',
                            'epci' => '200054781',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->administrativeBoundariesGeometry
            ->expects($this->once())
            ->method('findByCodes')
            ->with('200054781', OrganizationCodeTypeEnum::EPCI->value)
            ->willReturn($expectedGeometry);

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('METROPOLE DU GRAND PARIS', $result->name);
        $this->assertEquals('200054781', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::EPCI->value, $result->codeType);
        $this->assertEquals($expectedGeometry, $result->geometry);
    }

    public function testFindBySiretNotFound(): void
    {
        $siret = '12345678901234'; // SIRET non existant

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

        $this->expectException(OrganizationNotFoundException::class);
        $this->apiOrganizationFetcher->findBySiret($siret);
    }

    public function testFindBySiretUnsupportedOrganizationType(): void
    {
        $siret = '98765432101234';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'total_results' => 1,
                'results' => [
                    [
                        'nom_complet' => 'ORGANISATION NON SUPPORTÉE',
                        'nature_juridique' => '9999', // Type non supporté
                        'siege' => [
                            'code_postal' => '75000',
                            'departement' => '75',
                            'region' => '11',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Impossible to get organization geometry',
                $this->callback(function (array $context) use ($siret): bool {
                    return $context['siret'] === $siret
                        && $context['name'] === 'ORGANISATION NON SUPPORTÉE'
                        && $context['nature_juridique'] === '9999';
                }),
            );

        $result = $this->apiOrganizationFetcher->findBySiret($siret);

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('ORGANISATION NON SUPPORTÉE', $result->name);
        $this->assertNull($result->code);
        $this->assertNull($result->codeType);
        $this->assertNull($result->geometry);
    }
}

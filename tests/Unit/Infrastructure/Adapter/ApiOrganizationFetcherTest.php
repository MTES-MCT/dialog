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
    private ApiOrganizationFetcher $apiOrganizationFetcher;

    protected function setUp(): void
    {
        $this->organizationFetcherClient = $this->createMock(HttpClientInterface::class);
        $this->apiOrganizationFetcher = new ApiOrganizationFetcher(
            $this->organizationFetcherClient,
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
                        'siege' => [
                            'commune' => '44195',
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('21440195200129');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('COMMUNE DE SAVENAY', $result->name);
        $this->assertEquals('44195', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::INSEE->value, $result->codeType);
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
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('22930008201453');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('DEPARTEMENT DE LA SEINE SAINT DENIS', $result->name);
        $this->assertEquals('93', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::DEPARTMENT->value, $result->codeType);
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
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('23750007900312');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('REGION ILE DE FRANCE', $result->name);
        $this->assertEquals('11', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::REGION->value, $result->codeType);
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
                        ],
                    ],
                ],
            ]);

        $this->organizationFetcherClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->apiOrganizationFetcher->findBySiret('20005478100022');

        $this->assertInstanceOf(OrganizationFetchedView::class, $result);
        $this->assertEquals('METROPOLE DU GRAND PARIS', $result->name);
        $this->assertEquals('200054781', $result->code);
        $this->assertEquals(OrganizationCodeTypeEnum::EPCI->value, $result->codeType);
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

        $this->expectException(OrganizationNotFoundException::class);
        $this->apiOrganizationFetcher->findBySiret('12345678901234');
    }

    public function testFindBySiretUnsupportedOrganizationType(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

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

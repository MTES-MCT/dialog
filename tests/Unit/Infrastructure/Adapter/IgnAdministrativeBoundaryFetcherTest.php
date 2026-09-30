<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Infrastructure\Adapter\IgnAdministrativeBoundaryFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class IgnAdministrativeBoundaryFetcherTest extends TestCase
{
    private const GEOMETRY = [
        'type' => 'MultiPolygon',
        'coordinates' => [[[[-2.5, 47.2], [-1.0, 47.2], [-1.0, 47.8], [-2.5, 47.2]]]],
    ];

    /**
     * @dataProvider provideLayers
     */
    public function testFetch(OrganizationCodeTypeEnum $codeType, string $code, string $expectedTypeName, string $expectedFilter): void
    {
        $client = new MockHttpClient(
            function (string $method, string $url, array $options) use ($expectedTypeName, $expectedFilter): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertStringStartsWith('http://testserver/wfs/ows?', $url);
                $this->assertSame(
                    [
                        'SERVICE' => 'WFS',
                        'VERSION' => '2.0.0',
                        'REQUEST' => 'GetFeature',
                        'TYPENAMES' => $expectedTypeName,
                        'CQL_FILTER' => $expectedFilter,
                        'SRSNAME' => 'EPSG:4326',
                        'OUTPUTFORMAT' => 'application/json',
                        'COUNT' => 1,
                    ],
                    $options['query'],
                );
                $this->assertSame(5.0, (float) $options['timeout']);
                $this->assertSame(10.0, (float) $options['max_duration']);

                return new JsonMockResponse([
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'geometry' => self::GEOMETRY,
                            'properties' => ['nom_officiel' => 'Nom officiel'],
                        ],
                    ],
                ]);
            },
            'http://testserver',
        );

        $boundary = (new IgnAdministrativeBoundaryFetcher($client))->fetch($codeType, $code);

        $this->assertSame('Nom officiel', $boundary->name);
        $this->assertSame(json_encode(self::GEOMETRY), $boundary->geometry);
    }

    public function provideLayers(): array
    {
        return [
            'commune' => [OrganizationCodeTypeEnum::INSEE, '44109', 'ADMINEXPRESS-COG-CARTO.LATEST:commune', "code_insee='44109'"],
            'EPCI' => [OrganizationCodeTypeEnum::EPCI, '244400404', 'ADMINEXPRESS-COG-CARTO.LATEST:epci', "code_siren='244400404'"],
            'département' => [OrganizationCodeTypeEnum::DEPARTMENT, '44', 'ADMINEXPRESS-COG-CARTO.LATEST:departement', "code_insee='44'"],
            'région' => [OrganizationCodeTypeEnum::REGION, '52', 'ADMINEXPRESS-COG-CARTO.LATEST:region', "code_insee='52'"],
            // Les apostrophes sont échappées pour ne pas pouvoir altérer le filtre CQL.
            'apostrophe' => [OrganizationCodeTypeEnum::DEPARTMENT, "4'4", 'ADMINEXPRESS-COG-CARTO.LATEST:departement', "code_insee='4''4'"],
        ];
    }

    public function testFetchUsesCodeAsNameWhenOfficialNameIsMissing(): void
    {
        $client = new MockHttpClient(new JsonMockResponse([
            'features' => [['geometry' => self::GEOMETRY, 'properties' => []]],
        ]));

        $boundary = (new IgnAdministrativeBoundaryFetcher($client))->fetch(OrganizationCodeTypeEnum::DEPARTMENT, '44');

        $this->assertSame('44', $boundary->name);
    }

    public function testFetchThrowsNotFoundWhenNoFeature(): void
    {
        $client = new MockHttpClient(new JsonMockResponse(['type' => 'FeatureCollection', 'features' => []]));

        try {
            (new IgnAdministrativeBoundaryFetcher($client))->fetch(OrganizationCodeTypeEnum::DEPARTMENT, '99');
            $this->fail('Expected an AdministrativeBoundaryNotFoundException');
        } catch (AdministrativeBoundaryNotFoundException $exc) {
            $this->assertSame('departement', $exc->getCodeType());
            $this->assertSame('99', $exc->getBoundaryCode());
        }
    }

    public function testFetchThrowsUnavailableWhenFeatureHasNoGeometry(): void
    {
        $this->expectException(AdministrativeBoundaryUnavailableException::class);
        $this->expectExceptionMessage('Administrative boundary "44" of type "departement" has no geometry');

        $client = new MockHttpClient(new JsonMockResponse([
            'features' => [['geometry' => null, 'properties' => ['nom_officiel' => 'Loire-Atlantique']]],
        ]));

        (new IgnAdministrativeBoundaryFetcher($client))->fetch(OrganizationCodeTypeEnum::DEPARTMENT, '44');
    }

    /**
     * @dataProvider provideFailingResponses
     */
    public function testFetchThrowsUnavailableOnFailure(MockResponse|\Closure $response): void
    {
        $this->expectException(AdministrativeBoundaryUnavailableException::class);
        $this->expectExceptionMessageMatches('/^Failed to fetch administrative boundary "44" of type "departement": /');

        (new IgnAdministrativeBoundaryFetcher(new MockHttpClient($response)))->fetch(OrganizationCodeTypeEnum::DEPARTMENT, '44');
    }

    public function provideFailingResponses(): array
    {
        return [
            'erreur serveur' => [new MockResponse('Internal Server Error', ['http_code' => 500])],
            'réponse non JSON' => [new MockResponse('<ows:ExceptionReport/>', ['http_code' => 200])],
            'erreur réseau' => [static fn () => throw new TransportException('Connection timed out')],
        ];
    }
}

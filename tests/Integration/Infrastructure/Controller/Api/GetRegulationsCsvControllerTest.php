<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\Mock\IgnGeocoderMockClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class GetRegulationsCsvControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    /**
     * @return array{int, string, string|null}
     */
    private function export(array $query = [], string $method = 'GET'): array
    {
        $this->client ??= static::createClient();
        $this->client->request($method, '/api/regulations/csv', $query);
        $response = $this->client->getResponse();

        return [
            $response->getStatusCode(),
            (string) $this->client->getInternalResponse()->getContent(),
            $response->headers->get('content-type'),
        ];
    }

    public function testFullExportIsServedAsCsv(): void
    {
        [$status, $content, $contentType] = $this->export();

        $this->assertSame(200, $status);
        $this->assertSame('text/csv; charset=UTF-8', $contentType);
        $this->assertSecurityHeaders();

        // BOM UTF-8 + ligne d'en-tête.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('arrete_uuid;arrete_titre;arrete_categorie', $content);
        $this->assertStringContainsString('emprise_uuid;emprise_type;emprise_libelle', $content);

        // Au moins une ligne de données (une emprise) en plus de l'en-tête.
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertGreaterThan(1, \count($lines));
    }

    public function testExportWithMeasureTypeFilterHavingNoResult(): void
    {
        // Aucune mesure publiée n'est une limitation de vitesse à la date de test.
        [$status, $content, $contentType] = $this->export(['status' => 'all', 'measureType' => 'speedLimitation']);

        $this->assertSame(200, $status);
        $this->assertSame('text/csv; charset=UTF-8', $contentType);

        // Seule la ligne d'en-tête est présente.
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(1, $lines);
    }

    public function testFilteredExportIsStreamedAsCsv(): void
    {
        // Un filtre déclenche le calcul à la volée (StreamedResponse), pas le cache.
        [$status, $content, $contentType] = $this->export(['status' => 'current']);

        $this->assertSame(200, $status);
        $this->assertSame('text/csv; charset=UTF-8', $contentType);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('arrete_uuid;arrete_titre;arrete_categorie', $content);
    }

    public function testExportExcludingHeavyGoodsVehicle(): void
    {
        [$status, $content, $contentType] = $this->export(['includeHeavyGoodsVehicle' => 'false']);

        $this->assertSame(200, $status);
        $this->assertSame('text/csv; charset=UTF-8', $contentType);
        $this->assertStringContainsString('arrete_uuid;arrete_titre;arrete_categorie', $content);
    }

    public function testExportFilteredByAdministrativeBoundary(): void
    {
        // Seul l'arrêté Litteralis a une emprise dans le Nord.
        [$status, $content, $contentType] = $this->export(['status' => 'all', 'departmentCode' => '59']);

        $this->assertSame(200, $status);
        $this->assertSame('text/csv; charset=UTF-8', $contentType);

        // L'en-tête et l'unique emprise de cet arrêté.
        $lines = array_values(array_filter(explode("\n", trim($content))));
        $this->assertCount(2, $lines);
        $this->assertStringContainsString(RegulationOrderRecordFixture::UUID_LITTERALIS, $lines[1]);

        // Les filtres par EPCI et par région sont également disponibles.
        [$status, $content] = $this->export(['status' => 'all', 'epciCode' => '200054781', 'regionCode' => '11']);

        $this->assertSame(200, $status);
        $this->assertStringContainsString(RegulationOrderRecordFixture::UUID_PUBLISHED, $content);
        $this->assertStringNotContainsString(RegulationOrderRecordFixture::UUID_LITTERALIS, $content);
    }

    public function testExportRejectsUnknownAdministrativeBoundary(): void
    {
        [$status, $content, $contentType] = $this->export(['departmentCode' => '99']);

        $this->assertSame(400, $status);
        $this->assertSame('application/json', $contentType);
        $this->assertSame(['error' => 'Invalid "departmentCode" parameter'], json_decode($content, true));
    }

    public function testExportReturns503WhenAdministrativeBoundaryIsUnavailable(): void
    {
        [$status, $content, $contentType] = $this->export(['departmentCode' => IgnGeocoderMockClient::UNAVAILABLE_DEPARTMENT_CODE]);

        $this->assertSame(503, $status);
        $this->assertSame('application/json', $contentType);
        $this->assertArrayHasKey('error', json_decode($content, true));
    }

    public function testHeadFullExportReturnsContentLength(): void
    {
        $this->client ??= static::createClient();
        $this->client->request('HEAD', '/api/regulations/csv');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertNotNull($response->headers->get('content-length'));
        $this->assertGreaterThan(0, (int) $response->headers->get('content-length'));
    }

    public function testHeadFilteredExportReturnsNoBody(): void
    {
        $this->client ??= static::createClient();
        $this->client->request('HEAD', '/api/regulations/csv', ['status' => 'current']);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
    }

    public function testExportRejectsInvalidStatus(): void
    {
        [$status] = $this->export(['status' => 'invalid']);

        $this->assertSame(400, $status);
    }

    public function testExportRejectsInvalidMeasureType(): void
    {
        [$status] = $this->export(['measureType' => 'invalid']);

        $this->assertSame(400, $status);
    }

    public function testExportRejectsInvalidCategory(): void
    {
        [$status] = $this->export(['category' => 'invalid']);

        $this->assertSame(400, $status);
    }

    public function testExportRejectsInvalidDateStart(): void
    {
        [$status] = $this->export(['dateStart' => 'not-a-date']);

        $this->assertSame(400, $status);
    }

    public function testExportRejectsInvalidDateEnd(): void
    {
        [$status] = $this->export(['dateEnd' => 'not-a-date']);

        $this->assertSame(400, $status);
    }
}

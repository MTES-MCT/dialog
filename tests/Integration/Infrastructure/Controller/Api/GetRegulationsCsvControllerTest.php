<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class GetRegulationsCsvControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    /**
     * @return array{int, string, string|null}
     */
    private function export(array $query = []): array
    {
        $this->client ??= static::createClient();
        $this->client->request('GET', '/api/regulations/export.csv', $query);
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
}

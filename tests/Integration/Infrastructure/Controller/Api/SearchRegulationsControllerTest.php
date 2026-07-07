<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class SearchRegulationsControllerTest extends AbstractWebTestCase
{
    private const AUTH_HEADERS = [
        'HTTP_X_CLIENT_ID' => 'clientId',
        'HTTP_X_CLIENT_SECRET' => 'clientSecret',
    ];

    private ?KernelBrowser $client = null;

    private function search(array $query = [], array $headers = self::AUTH_HEADERS): array
    {
        $this->client ??= static::createClient();
        $this->client->request('GET', '/api/regulations/search', $query, [], $headers);

        return [$this->client->getResponse()->getStatusCode(), json_decode($this->client->getResponse()->getContent(), true)];
    }

    public function testSearchReturnsPublishedRegulationsForOrganization(): void
    {
        [$status, $data] = $this->search(['status' => 'all']);

        $this->assertSame(200, $status);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('regulations', $data);
        $this->assertIsArray($data['regulations']);
        $this->assertGreaterThanOrEqual(3, $data['metadata']['totalItems']);

        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('FO2/2023', $identifiers);
        $this->assertContains('F/CIFS/2023', $identifiers);

        foreach ($data['regulations'] as $regulation) {
            $this->assertSame(RegulationOrderRecordStatusEnum::PUBLISHED->value, $regulation['status']);
            $this->assertArrayHasKey('category', $regulation);
            $this->assertArrayHasKey('title', $regulation);
            $this->assertArrayHasKey('organization', $regulation);
            $this->assertArrayHasKey('measures', $regulation);
        }
    }

    public function testSearchDefaultsToCurrentStatus(): void
    {
        [$status, $data] = $this->search();

        $this->assertSame(200, $status);
        $this->assertArrayHasKey('regulations', $data);
    }

    public function testSearchFilterByMeasureType(): void
    {
        [$status, $data] = $this->search(['status' => 'all', 'measureType' => 'noEntry']);
        $this->assertSame(200, $status);
        $this->assertGreaterThanOrEqual(3, $data['metadata']['totalItems']);

        // Aucune mesure publiée de cette organisation n'est une limitation de vitesse.
        [$status, $data] = $this->search(['status' => 'all', 'measureType' => 'speedLimitation']);
        $this->assertSame(200, $status);
        $this->assertSame(0, $data['metadata']['totalItems']);
    }

    public function testSearchFilterByCategoryPermanentReturnsNothing(): void
    {
        // Les arrêtés permanents de cette organisation sont en brouillon, donc non publiés.
        [$status, $data] = $this->search(['status' => 'all', 'category' => 'permanentRegulation']);

        $this->assertSame(200, $status);
        $this->assertSame(0, $data['metadata']['totalItems']);
    }

    public function testSearchExcludeHeavyGoodsVehicle(): void
    {
        [$status, $data] = $this->search(['status' => 'all', 'includeHeavyGoodsVehicle' => 'false']);

        $this->assertSame(200, $status);
        $this->assertArrayHasKey('regulations', $data);
    }

    public function testSearchPagination(): void
    {
        [$status, $data] = $this->search(['status' => 'all', 'pageSize' => 1, 'page' => 1]);

        $this->assertSame(200, $status);
        $this->assertSame(1, $data['metadata']['pageSize']);
        $this->assertSame(1, $data['metadata']['page']);
        $this->assertLessThanOrEqual(1, \count($data['regulations']));
        $this->assertGreaterThanOrEqual(3, $data['metadata']['totalItems']);
        $this->assertGreaterThanOrEqual(3, $data['metadata']['lastPage']);
    }

    public function testSearchInvalidStatusReturns400(): void
    {
        [$status, $data] = $this->search(['status' => 'invalid']);

        $this->assertSame(400, $status);
        $this->assertArrayHasKey('error', $data);
    }

    public function testSearchInvalidMeasureTypeReturns400(): void
    {
        [$status, $data] = $this->search(['measureType' => 'notAType']);

        $this->assertSame(400, $status);
    }

    public function testSearchInvalidDateReturns400(): void
    {
        [$status] = $this->search(['dateStart' => 'not-a-date']);

        $this->assertSame(400, $status);
    }

    public function testSearchUnauthorized(): void
    {
        [$status] = $this->search(['status' => 'all'], [
            'HTTP_X_CLIENT_ID' => 'invalid',
            'HTTP_X_CLIENT_SECRET' => 'invalid',
        ]);

        $this->assertSame(401, $status);
    }
}

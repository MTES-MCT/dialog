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

    public function testSearchFilterByInseeCode(): void
    {
        [$status, $data] = $this->search(['status' => 'all', 'inseeCode' => '93070']);

        $this->assertSame(200, $status);
        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('FO2/2023', $identifiers);
        $this->assertContains('F/CIFS/2023', $identifiers);
        $this->assertNotContains('117374#24-A-0473', $identifiers);

        // Code INSEE ne concernant aucune emprise publiée de l'organisation.
        [$status, $data] = $this->search(['status' => 'all', 'inseeCode' => '00000']);
        $this->assertSame(200, $status);
        $this->assertSame(0, $data['metadata']['totalItems']);
    }

    public function testSearchStatusExpired(): void
    {
        // À la date de test (2023-06-09), seul FO2/2023 (mars 2023) est expiré.
        [$status, $data] = $this->search(['status' => 'expired']);

        $this->assertSame(200, $status);
        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('FO2/2023', $identifiers);
        $this->assertNotContains('F/CIFS/2023', $identifiers);
        $this->assertNotContains('117374#24-A-0473', $identifiers);
    }

    public function testSearchStatusUpcoming(): void
    {
        // À la date de test (2023-06-09), seul 117374#24-A-0473 (juillet 2023) est à venir.
        [$status, $data] = $this->search(['status' => 'upcoming']);

        $this->assertSame(200, $status);
        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('117374#24-A-0473', $identifiers);
        $this->assertNotContains('FO2/2023', $identifiers);
        $this->assertNotContains('F/CIFS/2023', $identifiers);
    }

    public function testSearchFilterByDateStart(): void
    {
        // Seul 117374#24-A-0473 est encore valide au 1er juillet 2023.
        [$status, $data] = $this->search(['status' => 'all', 'dateStart' => '2023-07-01']);

        $this->assertSame(200, $status);
        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('117374#24-A-0473', $identifiers);
        $this->assertNotContains('FO2/2023', $identifiers);
        $this->assertNotContains('F/CIFS/2023', $identifiers);
    }

    public function testSearchFilterByDateEnd(): void
    {
        // Seul FO2/2023 débute avant le 1er avril 2023.
        [$status, $data] = $this->search(['status' => 'all', 'dateEnd' => '2023-04-01']);

        $this->assertSame(200, $status);
        $identifiers = array_column($data['regulations'], 'identifier');
        $this->assertContains('FO2/2023', $identifiers);
        $this->assertNotContains('F/CIFS/2023', $identifiers);
        $this->assertNotContains('117374#24-A-0473', $identifiers);
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

    public function testSearchInvalidCategoryReturns400(): void
    {
        [$status, $data] = $this->search(['category' => 'notACategory']);

        $this->assertSame(400, $status);
        $this->assertArrayHasKey('error', $data);
    }

    public function testSearchInvalidDateStartReturns400(): void
    {
        [$status] = $this->search(['dateStart' => 'not-a-date']);

        $this->assertSame(400, $status);
    }

    public function testSearchInvalidDateEndReturns400(): void
    {
        [$status] = $this->search(['dateEnd' => 'not-a-date']);

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

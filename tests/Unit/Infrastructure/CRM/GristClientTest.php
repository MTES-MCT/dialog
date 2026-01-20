<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\CRM;

use App\Infrastructure\CRM\GristClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GristClientTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $docId;
    private GristClient $gristClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->docId = 'test-doc-id';

        $this->gristClient = new GristClient(
            $this->httpClient,
            $this->logger,
            $this->docId,
        );
    }

    public function testSyncData(): void
    {
        $tableId = 'test-table-id';
        $records = [
            [
                'require' => ['email' => 'mathieu@fairness.coop'],
                'fields' => [
                    'full_name' => 'Mathieu Marchois',
                    'email' => 'mathieu@fairness.coop',
                    'organisations' => 'DiaLog',
                    'registration_date' => '2024-01-01 00:00:00',
                    'last_activity_date' => '2024-01-01 00:00:00',
                ],
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'PUT',
                \sprintf('/api/docs/%s/tables/%s/records', $this->docId, $tableId),
                [
                    'json' => ['records' => $records],
                ],
            )
            ->willReturn($response);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Records synced to Grist',
                [
                    'count' => 1,
                    'statusCode' => 200,
                ],
            );

        $this->gristClient->syncData($records, $tableId);
    }
}

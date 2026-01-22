<?php

declare(strict_types=1);

namespace App\Infrastructure\CRM;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GristClient
{
    public function __construct(
        #[Autowire(service: 'grist.client')]
        private HttpClientInterface $gristClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'GRIST_DOC_ID')]
        private string $docId,
    ) {
    }

    public function syncData(array $records, string $tableId): void
    {
        $response = $this->gristClient->request('PUT', \sprintf('/api/docs/%s/tables/%s/records', $this->docId, $tableId), [
            'json' => ['records' => $records],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Error syncing records to Grist: ' . $response->getContent());
        }

        $this->logger->info('Records synced to Grist', [
            'count' => \count($records),
            'statusCode' => $response->getStatusCode(),
        ]);
    }

    public function getRecords(string $tableId): array
    {
        $response = $this->gristClient->request('GET', \sprintf('/api/docs/%s/tables/%s/records', $this->docId, $tableId));

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Error fetching records from Grist: ' . $response->getContent());
        }

        $data = $response->toArray();

        return $data['records'] ?? [];
    }
}

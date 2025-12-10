<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class IgnReportClient
{
    public function __construct(
        #[Autowire(service: 'ign.report.client')]
        private HttpClientInterface $ignReportClient,
        private LoggerInterface $logger,
    ) {
    }

    public function submitReport(
        string $comment,
        string $geometry,
    ): ResponseInterface {
        if (empty($comment)) {
            throw new \InvalidArgumentException('Comment is required');
        }

        if (empty($geometry)) {
            throw new \InvalidArgumentException('Geometry is required');
        }

        $payload = [
            'community' => 1,
            'geometry' => $geometry,
            'comment' => $comment,
            'status' => 'test',
            'attributes' => [
                'community' => 1,
                'theme' => 'Route',
                'attributes' => (object) [],
            ],
        ];

        $this->logger->info('Sending report to IGN API', [
            'geometry' => $geometry,
            'comment' => $comment,
        ]);

        try {
            $response = $this->ignReportClient->request('POST', '/', [
                'json' => $payload,
            ]);

            $this->logger->info('Report sent to IGN API', [
                'geometry' => $geometry,
                'comment' => $comment,
                'statusCode' => $response->getStatusCode(),
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send report to IGN API', [
                'geometry' => $geometry,
                'comment' => $comment,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

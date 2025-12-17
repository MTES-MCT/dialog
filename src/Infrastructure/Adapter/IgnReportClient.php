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
        #[Autowire(env: 'API_IGN_REPORT_AUTH')]
        private string $credentials,
        #[Autowire(env: 'IGN_REPORT_STATUS')]
        private string $defaultStatus = 'test',
    ) {
    }

    public function submitReport(
        string $comment,
        string $geometry,
        ?string $status = null,
    ): ResponseInterface {
        $status = $status ?? $this->defaultStatus;
        if (empty(trim($comment))) {
            throw new \InvalidArgumentException('Comment is required');
        }

        if (empty(trim($geometry))) {
            throw new \InvalidArgumentException('Geometry is required');
        }

        $taggedComment = '[DiaLog] ' . $comment;

        $payload = [
            'community' => 1,
            'geometry' => $geometry,
            'comment' => $taggedComment,
            'status' => $status,
            'attributes' => [
                'community' => 1,
                'theme' => 'Route',
                'attributes' => new \stdClass(),
            ],
        ];

        $response = $this->ignReportClient->request('POST', '/gcms/api/reports', [
            'json' => $payload,
            'auth_basic' => $this->credentials,
        ]);

        $this->logger->info('Report sent to IGN API', [
            'json_encoded' => json_encode($payload),
            'geometry' => $geometry,
            'comment' => $comment,
            'statusCode' => $response->getStatusCode(),
        ]);

        return $response;
    }
}

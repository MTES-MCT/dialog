<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Ign\IgnReportSubmissionResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnReportClient
{
    public function __construct(
        #[Autowire(service: 'ign.report.client')]
        private HttpClientInterface $ignReportClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'API_IGN_REPORT_AUTH')]
        private string $credentials,
        #[Autowire(env: 'IGN_REPORT_STATUS')]
        private string $status,
    ) {
    }

    /**
     * Envoie un signalement à l'API IGN. Retourne le résultat si l'API renvoie un body avec id (et optionnellement status).
     */
    public function submitReport(string $comment, string $geometry): ?IgnReportSubmissionResult
    {
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
            'status' => $this->status,
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

        $statusCode = $response->getStatusCode();
        $this->logger->info('Report sent to IGN API', [
            'json_encoded' => json_encode($payload),
            'geometry' => $geometry,
            'comment' => $comment,
            'statusCode' => $statusCode,
        ]);

        if ($statusCode < 200 || $statusCode >= 300) {
            return null;
        }

        try {
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $id = $data['id'] ?? null;
        if ($id === null) {
            return null;
        }

        return new IgnReportSubmissionResult(
            id: (string) $id,
            status: (string) ($data['status'] ?? $this->status),
        );
    }

    /**
     * Récupère le statut d'un signalement auprès de l'IGN (GET /gcms/api/reports/{id}).
     * Retourne null si l'API ne renvoie pas de statut ou en cas d'erreur.
     */
    public function getReportStatus(string $ignReportId): ?string
    {
        try {
            $response = $this->ignReportClient->request('GET', '/gcms/api/reports/' . $ignReportId, [
                'auth_basic' => $this->credentials,
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return null;
            }

            $data = $response->toArray(false);
            $status = $data['status'] ?? null;

            return $status !== null ? (string) $status : null;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get IGN report status', [
                'ignReportId' => $ignReportId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

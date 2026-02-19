<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Webhook;

use App\Application\DateUtilsInterface;
use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IgnReportStatusWebhookController
{
    private const HEADER_SECRET = 'X-IGN-Webhook-Secret';

    public function __construct(
        private ReportAddressRepositoryInterface $reportAddressRepository,
        private EntityManagerInterface $entityManager,
        private DateUtilsInterface $dateUtils,
        private MailerInterface $mailer,
        #[Autowire(env: 'IGN_WEBHOOK_SECRET')]
        private string $webhookSecret,
        #[Autowire(env: 'EMAIL_SUPPORT')]
        private string $emailSupport,
    ) {
    }

    /**
     * Webhook appelé par l'IGN pour notifier un changement de statut d'un signalement.
     * Corps attendu (JSON) : { "reportId": "123" ou "id": "123", "status": "treated" }
     * En-tête requis : X-IGN-Webhook-Secret avec la valeur configurée (IGN_WEBHOOK_SECRET).
     */
    #[Route(
        '/api/webhooks/ign-report-status',
        name: 'api_webhooks_ign_report_status',
        methods: ['POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $secret = $request->headers->get(self::HEADER_SECRET);
        if ($this->webhookSecret === '' || $secret !== $this->webhookSecret) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $reportId = $data['reportId'] ?? $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if ($reportId === null || $status === null || !\is_string($reportId) || !\is_string($status)) {
            return new JsonResponse(
                ['error' => 'Missing or invalid reportId/id or status'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $reportAddress = $this->reportAddressRepository->findOneByIgnReportId($reportId);
        if ($reportAddress === null) {
            return new JsonResponse(['error' => 'Report not found'], Response::HTTP_NOT_FOUND);
        }

        $reportAddress->setIgnReportStatus($status);
        $reportAddress->setIgnStatusUpdatedAt($this->dateUtils->getNow());
        $this->entityManager->flush();

        $this->sendStatusUpdateNotification($reportAddress, $status);

        return new JsonResponse(['ok' => true], Response::HTTP_OK);
    }

    private function sendStatusUpdateNotification(ReportAddress $reportAddress, string $status): void
    {
        try {
            $user = $reportAddress->getUser();
            $this->mailer->send(new Mail(
                address: $this->emailSupport,
                subject: 'contact.email.user_report_status_subject',
                template: 'email/user/user_report_status_updated.html.twig',
                payload: [
                    'content' => $reportAddress->getContent(),
                    'location' => $reportAddress->getLocation(),
                    'fullName' => $user->getFullName(),
                    'contactEmail' => $user->getEmail(),
                    'status' => $status,
                ],
            ));
        } catch (\Throwable $e) {
            // Ne pas faire échouer le webhook si l'envoi d'email échoue
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\EmailSendingException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\MailerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Mail;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Infrastructure\Adapter\IgnReportClient;
use Psr\Log\LoggerInterface;

final readonly class SaveReportAddressCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private ReportAddressRepositoryInterface $reportAddressRepository,
        private DateUtilsInterface $dateUtils,
        private IgnReportClient $ignReportClient,
        private OrganizationRepositoryInterface $organizationRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private string $emailSupport,
    ) {
    }

    public function __invoke(SaveReportAddressCommand $command): void
    {
        $reportAddress = new ReportAddress(
            uuid: $this->idFactory->make(),
            content: $command->content,
            location: $command->location,
            user: $command->user,
        );
        $reportAddress->setCreatedAt($this->dateUtils->getNow());

        $this->reportAddressRepository->add($reportAddress);

        $this->sendReportToIgn($command, $reportAddress);
        $this->sendReportByEmail($command);
    }

    private function sendReportByEmail(SaveReportAddressCommand $command): void
    {
        try {
            $this->mailer->send(new Mail(
                address: $this->emailSupport,
                subject: 'contact.email.user_report_subject',
                template: 'email/user/user_report.html.twig',
                payload: [
                    'content' => $command->content,
                    'location' => $command->location,
                    'fullName' => $command->user->getFullName(),
                    'contactEmail' => $command->user->getEmail(),
                ],
            ));
        } catch (\Exception $e) {
            throw new EmailSendingException('Failed to send feedback by email : ' . $e->getMessage());
        }
    }

    private function sendReportToIgn(SaveReportAddressCommand $command, ReportAddress $reportAddress): void
    {
        $comment = $command->content;
        $userId = $command->user->getUuid();

        // Try to get geometry from roadBanId first (for entire road), fallback to organization geometry
        $roadBanIdGeometry = $command->roadBanId ? $this->getGeometryFromRoadBanId($command->roadBanId) : null;
        $geometry = $roadBanIdGeometry ?? $this->getOrganizationPointGeometry($command);

        if (!$geometry) {
            $context = [
                'userId' => $userId,
                'organizationUuid' => $command->organizationUuid,
            ];

            if ($command->roadBanId) {
                $context['roadBanId'] = $command->roadBanId;
                $context['reason'] = 'roadBanId geocoding failed and organization geometry not found or invalid';
            } else {
                $context['reason'] = 'no roadBanId provided and organization geometry not found or invalid';
            }

            $this->logger->warning('Cannot send report to IGN API: geometry not found', $context);

            return;
        }

        try {
            $result = $this->ignReportClient->submitReport($comment, $geometry);
            if ($result) {
                $reportAddress->setIgnReportId($result->id);
                $reportAddress->setIgnReportStatus($result->status);
                $reportAddress->setIgnStatusUpdatedAt($this->dateUtils->getNow());
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send report to IGN API', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getGeometryFromRoadBanId(string $roadBanId): ?string
    {
        try {
            $geoJson = $this->roadGeocoder->computeRoadLine($roadBanId);

            $centroidGeoJson = $this->organizationRepository->computeCentroidFromGeoJson($geoJson);

            return $this->convertPointGeoJsonToWkt($centroidGeoJson);
        } catch (GeocodingFailureException $e) {
            $this->logger->error('Failed to get centroid geometry from roadBanId', [
                'roadBanId' => $roadBanId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getOrganizationPointGeometry(SaveReportAddressCommand $command): ?string
    {
        if (!$command->organizationUuid) {
            return null;
        }

        $organization = $this->organizationRepository->findOneByUuid($command->organizationUuid);

        if (!$organization || !$organization->getGeometry()) {
            return null;
        }

        // Calculate centroid (point central) with PostGIS instead of full geometry
        $centroidGeoJson = $this->organizationRepository->computeCentroidFromGeoJson($organization->getGeometry());

        return $this->convertPointGeoJsonToWkt($centroidGeoJson);
    }

    private function convertPointGeoJsonToWkt(string $geoJson): ?string
    {
        if (empty(trim($geoJson))) {
            return null;
        }

        $data = json_decode($geoJson, true);

        if (!$data || !isset($data['coordinates']) || !\is_array($data['coordinates']) || \count($data['coordinates']) < 2) {
            return null;
        }

        $lon = $data['coordinates'][0];
        $lat = $data['coordinates'][1];

        if ($lon === null || $lat === null || $lon === '' || $lat === '') {
            return null;
        }

        return \sprintf('POINT(%s %s)', $lon, $lat);
    }
}

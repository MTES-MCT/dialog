<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use App\Infrastructure\Adapter\IgnReportClient;
use Psr\Log\LoggerInterface;

final class SaveReportAddressCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private ReportAddressRepositoryInterface $reportAddressRepository,
        private DateUtilsInterface $dateUtils,
        private IgnReportClient $ignReportClient,
        private OrganizationRepositoryInterface $organizationRepository,
        private RoadGeocoderInterface $roadGeocoder,
        private LoggerInterface $logger,
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

        // Send report to IGN API
        $this->sendReportToIgn($command);
    }

    private function sendReportToIgn(SaveReportAddressCommand $command): void
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
            $this->ignReportClient->submitReport($comment, $geometry);
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
            // Get GeoJSON geometry from roadBanId (for entire road)
            $geoJson = $this->roadGeocoder->computeRoadLine($roadBanId);

            // Calculate centroid with PostGIS
            $centroidGeoJson = $this->organizationRepository->computeCentroidFromGeoJson($geoJson);

            // Convert GeoJSON Point to WKT
            return $this->convertPointGeoJsonToWkt($centroidGeoJson);
        } catch (GeocodingFailureException $e) {
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

        // Convert GeoJSON Point to WKT
        return $this->convertPointGeoJsonToWkt($centroidGeoJson);
    }

    private function convertPointGeoJsonToWkt(string $geoJson): string
    {
        $data = json_decode($geoJson, true);

        // GeoJSON Point: [lon, lat]
        return \sprintf('POINT(%s %s)', $data['coordinates'][0], $data['coordinates'][1]);
    }
}

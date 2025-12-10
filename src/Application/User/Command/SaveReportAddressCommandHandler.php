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

        // Try to get geometry from roadBanId first (for entire road)
        $geometry = null;

        if ($command->roadBanId) {
            $geometry = $this->getGeometryFromRoadBanId($command->roadBanId);
        }

        // If roadBanId geocoding failed or not provided, use organization geometry
        if (!$geometry) {
            $geometry = $this->getOrganizationGeometry($command);
        }

        if (!$geometry) {
            $this->logger->warning('Cannot send report to IGN API: geometry not found', [
                'userId' => $command->user->getUuid(),
                'organizationUuid' => $command->organizationUuid,
                'roadBanId' => $command->roadBanId,
            ]);

            return;
        }

        try {
            $this->ignReportClient->submitReport($comment, $geometry);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send report to IGN API', [
                'userId' => $command->user->getUuid(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getGeometryFromRoadBanId(string $roadBanId): ?string
    {
        try {
            // Get GeoJSON geometry from roadBanId (for entire road)
            $geoJson = $this->roadGeocoder->computeRoadLine($roadBanId);

            return $this->convertGeoJsonToWkt($geoJson);
        } catch (GeocodingFailureException $e) {
            $this->logger->info('Failed to get geometry from roadBanId, will use organization geometry', [
                'roadBanId' => $roadBanId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getOrganizationGeometry(SaveReportAddressCommand $command): ?string
    {
        $organization = $this->organizationRepository->findOneByUuid($command->organizationUuid);

        if (!$organization) {
            return null;
        }

        $geoJson = $organization->getGeometry();

        if (!$geoJson) {
            return null;
        }

        // Convert GeoJSON to WKT
        return $this->convertGeoJsonToWkt($geoJson);
    }

    private function convertGeoJsonToWkt(string $geoJson): ?string
    {
        $data = json_decode($geoJson, true);

        if (!$data || !isset($data['type']) || !isset($data['coordinates'])) {
            return null;
        }

        $type = $data['type'];
        $coordinates = $data['coordinates'];

        return match ($type) {
            'Point' => $this->pointToWkt($coordinates),
            'LineString' => $this->lineStringToWkt($coordinates),
            'Polygon' => $this->polygonToWkt($coordinates),
            'MultiPolygon' => $this->multiPolygonToWkt($coordinates),
            default => null,
        };
    }

    private function pointToWkt(array $coordinates): string
    {
        // GeoJSON: [lon, lat]
        return \sprintf('POINT(%s %s)', $coordinates[0], $coordinates[1]);
    }

    private function lineStringToWkt(array $coordinates): string
    {
        $points = array_map(
            fn (array $coord) => \sprintf('%s %s', $coord[0], $coord[1]),
            $coordinates,
        );

        return 'LINESTRING(' . implode(', ', $points) . ')';
    }

    private function polygonToWkt(array $coordinates): string
    {
        // Polygon is an array of rings (first is exterior, others are holes)
        $rings = array_map(
            fn (array $ring) => '(' . implode(', ', array_map(
                fn (array $coord) => \sprintf('%s %s', $coord[0], $coord[1]),
                $ring,
            )) . ')',
            $coordinates,
        );

        return 'POLYGON(' . implode(', ', $rings) . ')';
    }

    private function multiPolygonToWkt(array $coordinates): string
    {
        // MultiPolygon is an array of polygons, each polygon is an array of rings
        $polygons = array_map(
            function (array $polygon) {
                $rings = array_map(
                    fn (array $ring) => '(' . implode(', ', array_map(
                        fn (array $coord) => \sprintf('%s %s', $coord[0], $coord[1]),
                        $ring,
                    )) . ')',
                    $polygon,
                );

                return '(' . implode(', ', $rings) . ')';
            },
            $coordinates,
        );

        return 'MULTIPOLYGON(' . implode(', ', $polygons) . ')';
    }
}

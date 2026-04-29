<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\Exception\GeocodingAddressNotFoundException;
use App\Application\LaneSectionMakerInterface;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class GetLocationGeometryController
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
        private RoadSectionMakerInterface $roadSectionMaker,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/_fragment/location-geometry',
        methods: 'GET',
        name: 'fragment_location_geometry',
    )]
    public function __invoke(
        #[MapQueryParameter] RoadTypeEnum $roadType,
        #[MapQueryParameter] ?string $roadBanId = null,
        #[MapQueryParameter] ?string $fromHouseNumber = null,
        #[MapQueryParameter] ?string $toHouseNumber = null,
        #[MapQueryParameter] ?string $fromRoadBanId = null,
        #[MapQueryParameter] ?string $toRoadBanId = null,
        #[MapQueryParameter] ?string $roadName = null,
        #[MapQueryParameter] ?string $cityCode = null,
        #[MapQueryParameter] ?string $direction = null,
        #[MapQueryParameter] ?string $administrator = null,
        #[MapQueryParameter] ?string $roadNumber = null,
        #[MapQueryParameter] ?string $fromPointNumber = null,
        #[MapQueryParameter] ?string $toPointNumber = null,
        #[MapQueryParameter] ?string $fromSide = null,
        #[MapQueryParameter] ?string $toSide = null,
        #[MapQueryParameter] int $fromAbscissa = 0,
        #[MapQueryParameter] int $toAbscissa = 0,
    ): Response {
        try {
            $geometry = match ($roadType) {
                RoadTypeEnum::LANE => $this->getNamedStreetGeometry($roadBanId, $fromHouseNumber, $toHouseNumber, $fromRoadBanId, $toRoadBanId, $roadName ?? '', $cityCode ?? '', $direction ?? DirectionEnum::BOTH->value),
                RoadTypeEnum::DEPARTMENTAL_ROAD,
                RoadTypeEnum::NATIONAL_ROAD => $this->getNumberedRoadGeometry($roadType, $administrator, $roadNumber, $fromPointNumber, $toPointNumber, $fromSide, $toSide, $fromAbscissa, $toAbscissa, $direction ?? DirectionEnum::BOTH->value),
                default => throw new BadRequestHttpException(\sprintf('Unsupported roadType: %s', $roadType->value)),
            };
        } catch (GeocodingAddressNotFoundException) {
            return new JsonResponse('geocoding not found', Response::HTTP_NOT_FOUND);
        }

        if (!$geometry) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($geometry, json: true);
    }

    private function getNamedStreetGeometry(?string $roadBanId, ?string $fromHouseNumber, ?string $toHouseNumber, ?string $fromRoadBanId, ?string $toRoadBanId, string $roadName, string $cityCode, string $direction): ?string
    {
        if (!$roadBanId) {
            return null;
        }

        try {
            $fullGeometry = $this->roadGeocoder->computeRoadLine($roadBanId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to compute road line', ['roadBanId' => $roadBanId, 'exception' => $e]);

            return null;
        }

        $hasStart = $fromHouseNumber || $fromRoadBanId;
        $hasEnd = $toHouseNumber || $toRoadBanId;

        if (!$hasStart || !$hasEnd) {
            return $fullGeometry;
        }

        try {
            return $this->laneSectionMaker->computeSection(
                $fullGeometry,
                $roadBanId,
                $roadName,
                $cityCode,
                $direction,
                null,
                $fromHouseNumber,
                $fromRoadBanId,
                null,
                $toHouseNumber,
                $toRoadBanId,
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to compute lane section', ['roadBanId' => $roadBanId, 'exception' => $e]);

            throw $e;
        }
    }

    private function getNumberedRoadGeometry(RoadTypeEnum $roadType, ?string $administrator, ?string $roadNumber, ?string $fromPointNumber, ?string $toPointNumber, ?string $fromSide, ?string $toSide, int $fromAbscissa, int $toAbscissa, string $direction): ?string
    {
        if (!$administrator || !$roadNumber) {
            return null;
        }

        try {
            $fullGeometry = $this->roadGeocoder->computeRoad($roadType->value, $administrator, $roadNumber);
        } catch (\Exception $e) {
            $this->logger->error('Failed to compute road', ['roadType' => $roadType->value, 'administrator' => $administrator, 'roadNumber' => $roadNumber, 'exception' => $e]);

            return null;
        }

        if (!$fromPointNumber || !$toPointNumber || !$fromSide || !$toSide) {
            return $fullGeometry;
        }

        [$fromDepartmentCode, $fromPointNumberDecoded] = SaveNumberedRoadCommand::decodePointNumberWithDepartmentCode($fromPointNumber);
        [$toDepartmentCode, $toPointNumberDecoded] = SaveNumberedRoadCommand::decodePointNumberWithDepartmentCode($toPointNumber);

        try {
            return $this->roadSectionMaker->computeSection(
                $fullGeometry,
                $roadType->value,
                $administrator,
                $roadNumber,
                $fromDepartmentCode,
                $fromPointNumberDecoded,
                $fromSide,
                $fromAbscissa,
                $toDepartmentCode,
                $toPointNumberDecoded,
                $toSide,
                $toAbscissa,
                $direction,
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to compute road section', ['roadType' => $roadType->value, 'administrator' => $administrator, 'roadNumber' => $roadNumber, 'exception' => $e]);

            throw $e;
        }
    }
}

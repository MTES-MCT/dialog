<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\LaneSectionMakerInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class GetLocationGeometryController
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LaneSectionMakerInterface $laneSectionMaker,
    ) {
    }

    #[Route(
        '/_fragment/location-geometry',
        methods: 'GET',
        name: 'fragment_location_geometry',
    )]
    public function __invoke(Request $request): Response
    {
        $roadType = $request->query->get('roadType');

        if (!$roadType) {
            throw new BadRequestHttpException('Missing roadType parameter');
        }

        $geometry = match ($roadType) {
            'lane' => $this->getNamedStreetGeometry($request),
            'departmentalRoad', 'nationalRoad' => $this->getNumberedRoadGeometry($request),
            default => throw new BadRequestHttpException(\sprintf('Unsupported roadType: %s', $roadType)),
        };

        if (!$geometry) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($geometry, json: true);
    }

    private function getNamedStreetGeometry(Request $request): ?string
    {
        $roadBanId = $request->query->get('roadBanId');

        if (!$roadBanId) {
            return null;
        }

        try {
            $fullGeometry = $this->roadGeocoder->computeRoadLine($roadBanId);

            $fromHouseNumber = $request->query->get('fromHouseNumber');
            $toHouseNumber = $request->query->get('toHouseNumber');
            $fromRoadBanId = $request->query->get('fromRoadBanId');
            $toRoadBanId = $request->query->get('toRoadBanId');

            $hasStart = $fromHouseNumber || $fromRoadBanId;
            $hasEnd = $toHouseNumber || $toRoadBanId;

            if (!$hasStart && !$hasEnd) {
                return $fullGeometry;
            }

            $roadName = $request->query->get('roadName', '');
            $cityCode = $request->query->get('cityCode', '');
            $direction = $request->query->get('direction', DirectionEnum::BOTH->value);

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
        } catch (\Exception) {
            return null;
        }
    }

    private function getNumberedRoadGeometry(Request $request): ?string
    {
        $roadType = $request->query->get('roadType');
        $administrator = $request->query->get('administrator');
        $roadNumber = $request->query->get('roadNumber');

        if (!$administrator || !$roadNumber) {
            return null;
        }

        try {
            return $this->roadGeocoder->computeRoad($roadType, $administrator, $roadNumber);
        } catch (\Exception) {
            return null;
        }
    }
}

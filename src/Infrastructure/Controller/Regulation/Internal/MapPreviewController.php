<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Internal;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class MapPreviewController
{
    private const DEFAULT_CENTER_LON = 2.725;
    private const DEFAULT_CENTER_LAT = 47.16;
    private const DEFAULT_ZOOM = 5.0;
    private const RENDER_WIDTH = 600;
    private const RENDER_HEIGHT = 420;
    private const FIT_PADDING_PX = 40;

    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_internal/regulation-map/{uuid}.html',
        name: 'app_regulation_internal_map_preview',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function html(string $uuid, Request $request): Response
    {
        [$centerLon, $centerLat, $zoom] = $this->initialViewport($request->query->get('bounds'));

        $html = $this->twig->render('regulation/_internal_map_preview.html.twig', [
            'geoJsonUrl' => \sprintf('/_internal/regulation-map/%s.geojson', $uuid),
            'mapCenterLon' => $centerLon,
            'mapCenterLat' => $centerLat,
            'mapZoom' => $zoom,
        ]);

        return new Response($html);
    }

    /**
     * Parse the optional `bounds=minLon,minLat,maxLon,maxLat` query param and derive a (center, zoom)
     * sized to the export viewport so MapLibre's first tile load already targets the right area.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function initialViewport(?string $boundsParam): array
    {
        if ($boundsParam === null) {
            return [self::DEFAULT_CENTER_LON, self::DEFAULT_CENTER_LAT, self::DEFAULT_ZOOM];
        }

        $parts = explode(',', $boundsParam);

        if (\count($parts) !== 4) {
            return [self::DEFAULT_CENTER_LON, self::DEFAULT_CENTER_LAT, self::DEFAULT_ZOOM];
        }

        $minLon = (float) $parts[0];
        $minLat = (float) $parts[1];
        $maxLon = (float) $parts[2];
        $maxLat = (float) $parts[3];

        $centerLon = ($minLon + $maxLon) / 2;
        $centerLat = ($minLat + $maxLat) / 2;

        // Web Mercator zoom that fits the bounds inside (width - 2*padding, height - 2*padding).
        $worldDim = 256.0;
        $usableWidth = max(1, self::RENDER_WIDTH - 2 * self::FIT_PADDING_PX);
        $usableHeight = max(1, self::RENDER_HEIGHT - 2 * self::FIT_PADDING_PX);

        $latRad = static fn (float $lat): float => log(tan(M_PI / 4 + ($lat * M_PI / 180) / 2));
        $latFraction = max(1e-9, ($latRad($maxLat) - $latRad($minLat)) / M_PI);
        $lonFraction = max(1e-9, ($maxLon - $minLon) / 360.0);

        $latZoom = log(($usableHeight / $worldDim) / $latFraction, 2);
        $lonZoom = log(($usableWidth / $worldDim) / $lonFraction, 2);
        $zoom = max(0.0, min($latZoom, $lonZoom, 18.0));

        return [$centerLon, $centerLat, $zoom];
    }

    #[Route(
        '/_internal/regulation-map/{uuid}.geojson',
        name: 'app_regulation_internal_map_geojson',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function geojson(string $uuid): JsonResponse
    {
        $rows = $this->locationRepository->findGeometriesForRegulationOrderRecord($uuid);
        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geometry'], true),
                'properties' => ['measure_type' => $row['measure_type']],
            ];
        }

        return new JsonResponse([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}

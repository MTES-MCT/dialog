<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/draw-map', name: 'app_draw_map')]
final class DrawMapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    #[Route(name: '_get', methods: ['GET'])]
    public function get(): Response
    {
        return new Response($this->twig->render('draw_map.html.twig'));
    }

    #[Route(name: '_post', methods: ['POST'])]
    public function post(Request $request): Response
    {
        return new Response($request->getContent());
    }

    #[Route('/nearby-streets', name: '_nearby_streets', methods: ['POST'])]
    public function nearbyStreets(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['geometry'])) {
            return new JsonResponse(['error' => 'GeoJSON geometry required'], Response::HTTP_BAD_REQUEST);
        }

        $radius = min((int) ($data['radius'] ?? 100), 500);
        $limit = min((int) ($data['limit'] ?? 10), 50);

        $streets = $this->roadGeocoder->findNearbyStreets(
            json_encode($data['geometry']),
            $radius,
            $limit,
        );

        return new JsonResponse($streets);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetNearbyStreetsQuery;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetNearbyStreetsController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/api/nearby-streets',
        name: 'api_nearby_streets',
        methods: ['POST'],
    )]
    #[OA\Tag(name: 'Public')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['geometry'],
            properties: [
                new OA\Property(
                    property: 'geometry',
                    type: 'object',
                    description: 'Géométrie GeoJSON',
                    example: '{"type": "Point", "coordinates": [2.35, 48.85]}',
                ),
                new OA\Property(
                    property: 'radius',
                    type: 'integer',
                    description: 'Rayon de recherche en mètres (max 500)',
                    example: 100,
                ),
                new OA\Property(
                    property: 'limit',
                    type: 'integer',
                    description: 'Nombre maximum de résultats (max 50)',
                    example: 10,
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des rues à proximité',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'roadName', type: 'string', example: 'Rue de Rivoli'),
                    new OA\Property(property: 'distance', type: 'number', format: 'float', example: 12.3),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 400,
        description: 'Requête invalide (géométrie GeoJSON manquante)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'GeoJSON geometry required'),
            ],
        ),
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur interne lors de la recherche',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Nearby streets query failed'),
            ],
        ),
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        if (!$data || !isset($data['geometry'])) {
            return new JsonResponse(['error' => 'GeoJSON geometry required'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($data['geometry']) || !isset($data['geometry']['type'])) {
            return new JsonResponse(['error' => 'Invalid GeoJSON geometry'], Response::HTTP_BAD_REQUEST);
        }

        $radius = min((int) ($data['radius'] ?? 100), 500);
        $limit = min((int) ($data['limit'] ?? 10), 50);

        try {
            $streets = $this->queryBus->handle(
                new GetNearbyStreetsQuery(
                    geometry: json_encode($data['geometry']),
                    radius: $radius,
                    limit: $limit,
                ),
            );
        } catch (GeocodingFailureException $exc) {
            $this->logger->error('Nearby streets query failed', [
                'exception' => $exc->getMessage(),
            ]);

            return new JsonResponse(
                ['error' => 'Nearby streets query failed'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        if (empty($streets)) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($streets);
    }
}

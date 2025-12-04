<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Statistics;

use App\Application\QueryBusInterface;
use App\Application\Statistics\Query\GetOrganizationsGeometriesQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrganizationsGeometriesController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/stats',
        methods: 'GET',
        name: 'api_stats_organizations_geometries',
    )]
    #[OA\Tag(name: 'Public')]
    #[OA\Response(
        response: 200,
        description: 'Zones géographiques fusionnées au format GeoJSON',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'FeatureCollection'),
                new OA\Property(
                    property: 'features',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'type', type: 'string', example: 'Feature'),
                            new OA\Property(
                                property: 'geometry',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'Polygon'),
                                    new OA\Property(
                                        property: 'coordinates',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'array',
                                            items: new OA\Items(
                                                type: 'array',
                                                items: new OA\Items(type: 'number'),
                                            ),
                                        ),
                                    ),
                                ],
                            ),
                            new OA\Property(
                                property: 'properties',
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'clusterName',
                                        type: 'string',
                                        nullable: true,
                                        example: 'Ville de Paris, Ville de Lyon',
                                        description: 'Nom agrégé des organisations incluses dans ce cluster',
                                    ),
                                ],
                            ),
                        ],
                    ),
                ),
            ],
        ),
    )]
    public function __invoke(): JsonResponse
    {
        $data = $this->queryBus->handle(new GetOrganizationsGeometriesQuery());

        return new JsonResponse(
            $data,
            Response::HTTP_OK,
        );
    }
}

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
        description: 'Géométries des organisations au format GeoJSON',
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
                                    new OA\Property(property: 'uuid', type: 'string'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'code', type: 'string', nullable: true),
                                    new OA\Property(property: 'codeType', type: 'string', nullable: true),
                                    new OA\Property(property: 'departmentName', type: 'string', nullable: true),
                                    new OA\Property(property: 'departmentCode', type: 'string', nullable: true),
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

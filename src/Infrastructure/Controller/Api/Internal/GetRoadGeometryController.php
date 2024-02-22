<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Internal;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRoadGeometryQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetRoadGeometryController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/internal/road_geometry',
        methods: 'GET',
        name: 'api_internal_roadGeometry',
    )]
    public function __invoke(Request $request): Response
    {
        $roadName = $request->query->get('roadName');
        $cityCode = $request->query->get('cityCode');

        $geometry = $this->queryBus->handle(new GetRoadGeometryQuery($roadName, $cityCode));

        return new Response(
            $this->twig->render('common/fetch_result.html.twig', [
                'value' => $geometry,
            ]),
        );
    }
}

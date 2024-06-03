<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Location\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetLocationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/map/fragments/location/{uuid}',
        name: 'fragment_map_location',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid = ''): Response
    {
        $location = $this->queryBus->handle(new GetLocationByUuidQuery($uuid));
        if (!$location instanceof Location) {
            throw new NotFoundHttpException();
        }

        $measure = $location->getMeasure();
        $measureView = MeasureView::fromEntity($measure);
        $regulation = $measure->getRegulationOrder();
        $regulationOrderRecordId = $regulation->getRegulationOrderRecord()->getUuid();

        return new Response(
            $this->twig->render(
                name: 'map/fragments/location_popup.html.twig',
                context: [
                    'location' => $location,
                    'measure' => $measureView,
                    'regulation' => $regulation,
                    'regulationOrderRecordId' => $regulationOrderRecordId,
                ],
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class GetNumberedRoadCompletionFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    #[Route(
        '/_fragment/road-number-completions',
        methods: 'GET',
        name: 'fragment_road_number_completion',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');
        $administrator = $request->query->get('administrator');
        $roadType = $request->query->get('roadType');

        if (!$administrator || !$roadType) {
            throw new BadRequestHttpException();
        }

        $results = $this->roadGeocoder->findRoads($search, $roadType, $administrator);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_road_numbers_completions.html.twig',
                context: [
                    'results' => $results,
                ],
            ),
        );
    }
}

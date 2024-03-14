<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetDepartmentalRoadCompletionFragmentController
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

        if (!$administrator) {
            throw new BadRequestHttpException();
        }

        $departmentalRoadNumbers = $this->roadGeocoder->findDepartmentalRoads($search, $administrator);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_road_numbers_completions.html.twig',
                context: [
                    'departmentalRoadNumbers' => $departmentalRoadNumbers,
                ],
            ),
        );
    }
}

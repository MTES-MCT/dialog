<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\MapGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class MapSearchController
{
    public function __construct(
        private \Twig\Environment $twig,
        private MapGeocoderInterface $mapGeocoder,
    ) {
    }

    #[Route(
        '/carte/search',
        name: 'fragment_carto_search',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');

        if (!$search) {
            throw new BadRequestHttpException();
        }

        $results = $this->mapGeocoder->findPlaces($search);

        return new Response(
            $this->twig->render(
                name: 'map/fragments/search_results.html.twig',
                context: [
                    'results' => $results,
                ],
            ),
        );
    }
}

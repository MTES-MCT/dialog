<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\MapGeocoderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

final class SearchMapFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private MapGeocoderInterface $mapGeocoder,
    ) {
    }

    #[Route(
        '/_fragment/map/search',
        name: 'fragment_map_search',
        methods: ['GET'],
    )]
    public function __invoke(
        #[MapQueryParameter] string $search = '',
    ): Response {
        $results = $search ? $this->mapGeocoder->findPlaces($search) : [];

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

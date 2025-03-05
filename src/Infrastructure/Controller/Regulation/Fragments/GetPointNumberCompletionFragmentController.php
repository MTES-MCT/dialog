<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

final class GetPointNumberCompletionFragmentController
{
    public function __construct(
        private \Twig\Environment $twig,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    #[Route(
        '/_fragment/point-number-completions',
        name: 'fragment_point_number_completion',
        methods: ['GET'],
    )]
    public function __invoke(
        #[MapQueryParameter] string $search,
        #[MapQueryParameter] string $administrator,
        #[MapQueryParameter] string $roadNumber,
    ): Response {
        $results = $this->roadGeocoder->findReferencePoints($search, $administrator, $roadNumber);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_point_number_completions.html.twig',
                context: [
                    'results' => $results,
                ],
            ),
        );
    }
}

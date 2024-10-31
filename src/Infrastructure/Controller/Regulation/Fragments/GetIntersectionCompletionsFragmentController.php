<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\IntersectionGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetIntersectionCompletionsFragmentController
{
    public function __construct(
        private IntersectionGeocoderInterface $intersectionGeocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/intersection-completions',
        methods: 'GET',
        name: 'fragment_intersection_completions',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $roadName = $request->query->get('roadName');
        $cityCode = $request->query->get('cityCode');

        if (!$roadName || !$cityCode) {
            throw new BadRequestHttpException();
        }

        $roadNames = $this->intersectionGeocoder->findIntersectingRoadNames($search, $roadName, $cityCode);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_intersection_completions.html.twig',
                context: [
                    'roadNames' => $roadNames,
                ],
            ),
        );
    }
}

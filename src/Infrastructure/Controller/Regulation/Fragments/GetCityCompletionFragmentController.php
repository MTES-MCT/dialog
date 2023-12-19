<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\GeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetCityCompletionFragmentController
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/city-completions',
        methods: 'GET',
        name: 'fragment_city_completion',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');

        if (!$search) {
            throw new BadRequestHttpException();
        }

        $cities = $this->geocoder->findCities($search);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_city_completions.html.twig',
                context: [
                    'cities' => $cities,
                ],
            ),
        );
    }
}

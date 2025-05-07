<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetAddressCompletionFragmentController
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/named-street-completions',
        methods: 'GET',
        name: 'fragment_namedStreet_completion',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');
        $cityCode = $request->query->get('cityCode');

        if (!$search || !$cityCode) {
            throw new BadRequestHttpException();
        }

        $namedStreets = $this->roadGeocoder->findNamedStreets($search, $cityCode);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_named_street_completions.html.twig',
                context: [
                    'namedStreets' => $namedStreets,
                ],
            ),
        );
    }
}

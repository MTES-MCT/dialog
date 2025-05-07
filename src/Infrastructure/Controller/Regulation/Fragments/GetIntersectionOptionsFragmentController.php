<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\IntersectionGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

final class GetIntersectionOptionsFragmentController
{
    public function __construct(
        private IntersectionGeocoderInterface $intersectionGeocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/intersection-options',
        methods: 'GET',
        name: 'fragment_intersection_options',
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $roadBanId,
        #[MapQueryParameter] string $currentOptions,
        #[MapQueryParameter] string $targetIds,
    ): Response {
        $namedStreets = $this->intersectionGeocoder->findIntersectingNamedStreets($roadBanId);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_intersection_options.html.twig',
                context: [
                    'namedStreets' => $namedStreets,
                    'currentOptions' => json_decode($currentOptions, true),
                    'targetIds' => json_decode($targetIds, true),
                ],
            ),
        );
    }
}

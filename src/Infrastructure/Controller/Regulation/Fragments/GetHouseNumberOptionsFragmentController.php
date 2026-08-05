<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\HouseNumberGeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class GetHouseNumberOptionsFragmentController
{
    public function __construct(
        private HouseNumberGeocoderInterface $houseNumberGeocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/house-number-options',
        methods: 'GET',
        name: 'fragment_house_number_options',
    )]
    public function __invoke(
        Request $request,
        #[MapQueryParameter] string $roadBanId,
        #[MapQueryParameter] string $targetIds,
    ): Response {
        $houseNumbers = $this->houseNumberGeocoder->findHouseNumbers($roadBanId);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_house_number_options.html.twig',
                context: [
                    'houseNumbers' => $houseNumbers,
                    'targetIds' => json_decode($targetIds, true),
                ],
            ),
        );
    }
}

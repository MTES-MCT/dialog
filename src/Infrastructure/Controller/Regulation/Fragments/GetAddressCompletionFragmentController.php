<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\GeocoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetAddressCompletionFragmentController
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/_fragment/address-completions',
        methods: 'GET',
        name: 'fragment_address_completion',
    )]
    public function __invoke(Request $request): Response
    {
        $search = $request->query->get('search');

        if (!$search) {
            throw new BadRequestHttpException();
        }

        $addresses = $this->geocoder->findAddresses($search);

        return new Response(
            $this->twig->render(
                name: 'regulation/fragments/_address_completions.html.twig',
                context: [
                    'addresses' => $addresses,
                ],
            ),
        );
    }
}

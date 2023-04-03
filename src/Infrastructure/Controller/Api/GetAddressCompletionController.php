<?php

// curl 'https://api-adresse.data.gouv.fr/search/?q=Sottev&type=street&autocomplete=1'

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\GeocoderInterface;
use App\Application\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetAddressCompletionController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private GeocoderInterface $geocoder,
    ) {
    }

    #[Route(
        '/api/address-completion',
        methods: 'GET',
        name: 'api_address_completion',
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->query->get('search');

        if (!$search) {
            throw new BadRequestHttpException('TODO: message');
        }

        $addresses = $this->geocoder->findAddresses($search);

        return new JsonResponse(['addresses' => $addresses]);
    }
}

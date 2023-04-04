<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\GeocoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class GetAddressCompletionController
{
    public function __construct(
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
        $type = $request->query->get('type');

        if (!$search || ($type !== 'street' && $type !== 'municipality')) {
            throw new BadRequestHttpException();
        }

        $addresses = $this->geocoder->findAddresses($type, $search);

        return new JsonResponse(['addresses' => $addresses]);
    }
}

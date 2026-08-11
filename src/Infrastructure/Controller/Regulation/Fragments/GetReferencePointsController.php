<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Fragments;

use App\Application\RoadGeocoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class GetReferencePointsController
{
    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/_fragment/reference-points',
        methods: 'GET',
        name: 'fragment_reference_points',
    )]
    public function __invoke(
        #[MapQueryParameter] string $administrator,
        #[MapQueryParameter] string $roadNumber,
    ): Response {
        if (!$administrator || !$roadNumber) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        try {
            $geometry = $this->roadGeocoder->computeReferencePoints($administrator, $roadNumber);
        } catch (\Exception $e) {
            $this->logger->error('Failed to compute reference points', ['administrator' => $administrator, 'roadNumber' => $roadNumber, 'exception' => $e]);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($geometry, json: true);
    }
}

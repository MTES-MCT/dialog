<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MapTilesController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private LocationRepositoryInterface $locationRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    #[Route(
        '/carte/tiles/{z}/{x}/{y}.mvt',
        name: 'app_carto_tiles',
        requirements: [
            'z' => '\d+',
            'x' => '\d+',
            'y' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, int $z, int $x, int $y): Response
    {
        $maxIndex = (1 << $z) - 1;
        if ($x < 0 || $x > $maxIndex || $y < 0 || $y > $maxIndex) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $dto = new MapFilterDTO($this->dateUtils->getNow());
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'method' => 'GET',
                'csrf_protection' => false,
            ],
        );
        $form->handleRequest($request);

        $mvt = $this->locationRepository->findRestrictionsAsMVT(
            $z,
            $x,
            $y,
            $dto->displayPermanentRegulations,
            $dto->displayTemporaryRegulations,
            $dto->measureTypes,
            $dto->startDate,
            $dto->endDate,
            $dto->displayHeavyGoodsVehicles,
        );

        if ($mvt === '') {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new Response(
            $mvt,
            headers: [
                'Content-Type' => 'application/vnd.mapbox-vector-tile',
                // The route is served by a stateless firewall (no PHPSESSID),
                // so shared caches (browser, CDN, reverse proxy) can store this response.
                // stale-while-revalidate keeps the UI snappy when a tile just expired.
                'Cache-Control' => 'public, max-age=300, s-maxage=300, stale-while-revalidate=300',
            ],
        );
    }
}

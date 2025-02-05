<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\DateUtilsInterface;
use App\Domain\Geography\Coordinates;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    #[Route(
        '/carte',
        name: 'app_carto',
        methods: ['GET'],
    )]
    public function __invoke(
        #[MapQueryParameter] ?float $lon = null,
        #[MapQueryParameter] ?float $lat = null,
        #[MapQueryParameter] float $zoom = 5,
    ): Response {
        $dto = new MapFilterDTO($this->dateUtils->getNow());
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_carto_data'),
                'method' => 'GET',
            ],
        );

        $mapCenter = Coordinates::fromLonLat(2.725, 47.7); // Metropolitan France center

        if (!empty($lon)) {
            $mapCenter = Coordinates::fromLonLat($lon, $mapCenter->latitude);
        }

        if (!empty($lat)) {
            $mapCenter = Coordinates::fromLonLat($mapCenter->longitude, $lat);
        }

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'form' => $form->createView(),
                    'mapCenter' => $mapCenter,
                    'mapZoom' => $zoom,
                ],
            ),
        );
    }
}

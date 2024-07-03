<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Turbo\TurboBundle;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/carte',
        name: 'app_carto',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $dto = new MapFilterDTO();
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_carto'),
                'method' => 'GET',
                'attr' => [
                    'data-turbo-action' => 'replace',
                ],
            ],
        );
        $form->handleRequest($request);

        $tolerance = $this->convertZoomLevelToToleranceInMeters($dto->zoomLevel);

        $locationsAsGeoJson = $this->locationRepository->findAllForMapAsGeoJSON(
            $dto->displayPermanentRegulations,
            $dto->displayTemporaryRegulations,
            $dto->displayFutureRegulations,
            $dto->displayPastRegulations,
            toleranceInMeters: $tolerance,
        );

        if ($form->isSubmitted() && $form->isValid()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT); // for the response (!)

            return new Response(
                $this->twig->render(
                    name: 'map/fragments/map_data.stream.html.twig',
                    context: [
                        'locationsAsGeoJson' => $locationsAsGeoJson,
                    ],
                ),
                headers: ['x-tolerance' => (string) $tolerance],
            );
        }

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'locationsAsGeoJson' => $locationsAsGeoJson,
                    'form' => $form->createView(),
                ],
            ),
            headers: ['x-tolerance' => (string) $tolerance],
        );
    }

    private function convertZoomLevelToToleranceInMeters(float $zoomLevel): float
    {
        // Formula and value for $a were derived from the table in this page at latitude ± 40:
        // https://docs.mapbox.com/help/glossary/zoom-level/
        $a = 59959.436;
        $metersPerPixel = $a * pow(2, -$zoomLevel);

        return 10 * $metersPerPixel;
    }
}

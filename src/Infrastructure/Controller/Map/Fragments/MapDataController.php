<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map\Fragments;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class MapDataController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/carte/data.geojson',
        name: 'app_carto_data',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $dto = new MapFilterDTO();
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_carto_data'),
                'method' => 'GET',
                'attr' => [
                    'data-turbo-action' => 'replace',
                ],
            ],
        );
        $form->handleRequest($request);

        $locationsAsGeoJson = $this->locationRepository->findAllForMapAsGeoJSON(
            $dto->displayPermanentRegulations,
            $dto->displayTemporaryRegulations,
            $dto->displayFutureRegulations,
            $dto->displayPastRegulations,
        );

        return new Response(
            $locationsAsGeoJson,
            headers: [
                'Content-Type' => 'application/json',
            ],
        );
    }
}

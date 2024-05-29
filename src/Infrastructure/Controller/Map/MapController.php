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
                'action' => $this->router->generate('get_map_filter'),
                'method' => 'GET',
                'attr' => [
                    'data-turbo-action' => 'replace',
                ],
            ],
        );
        $form->handleRequest($request); // auto-fill the form with the query parameters from the URL
        $locationsAsGeoJson = $this->locationRepository->findAllForMapAsGeoJSON(
            $dto->category === 'permanents_only',
            $dto->category === 'temporaries_only',
            $dto->displayFutureRegulations === 'yes',
            $dto->displayPastRegulations === 'yes',
        );

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'locationsAsGeoJson' => $locationsAsGeoJson,
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

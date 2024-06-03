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
use Symfony\UX\Turbo\TurboBundle;

final class GetMapDataController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/map/fragments/data',
        name: 'fragment_map_data',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $dto = new MapFilterDTO();
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
        );
        // $form->handleRequest($request); // auto-fill the form with the query parameters from the URL
        $locationsAsGeoJsonAsText = $this->locationRepository->findAllForMapAsGeoJSON(
            $dto->category === 'permanents_only',
            $dto->category === 'temporaries_only',
            $dto->displayFutureRegulations === '1',
            $dto->displayPastRegulations === '1',
        );

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT); // for the response (!)

        return new Response(
            $this->twig->render(
                name: 'map/fragments/map_data.stream.html.twig',
                context: [
                    'locationsAsGeoJson' => $locationsAsGeoJsonAsText,
                ],
            ),
        );
    }
}

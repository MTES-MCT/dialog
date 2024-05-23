<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class GetMapFilterController extends AbstractController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    #[Route(
        '/_get_map_filter',
        name: 'get_map_filter',
        methods: ['GET'],
    )]
    public function __invoke(Request $request, #[MapQueryParameter] array $map_filter_form = ['display_drafts' => 'no', 'category' => 'permanents_and_temporaries', 'display_future_regulations' => 'no']): Response
    {
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            options: [
                'action' => $this->router->generate('get_map_filter'),
                'method' => 'GET',
                'attr' => [
                    'data-turbo-action' => 'replace',
                ],
            ],
        );

        $form->handleRequest($request); // auto-fill the form with the query parameters from the URL

        // the array '$map_filter_form' can be defined without the 'display_drafts' key for example, so we have to set a default value eventually
        $permanentAndOrTemporaryFilter = $map_filter_form['category'] ?? 'permanents_and_temporaries';
        $draftFilter = $map_filter_form['display_drafts'] ?? 'no';
        $futureFilter = $map_filter_form['display_future_regulations'] ?? 'no';

        $locationsAsGeoJson = $this->locationRepository->findFilteredLocationsAsGeoJson($permanentAndOrTemporaryFilter, $draftFilter, $futureFilter);

        return new Response(
            $this->twig->render(
                name: '_map_filter.html.twig',
                context: [
                    'locationsAsGeoJson' => $locationsAsGeoJson,
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

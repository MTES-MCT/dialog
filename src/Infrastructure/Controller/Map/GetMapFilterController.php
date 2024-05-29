<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class GetMapFilterController
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
    public function __invoke(Request $request, #[MapQueryParameter] array $map_filter_form = ['category' => 'permanents_and_temporaries', 'display_future_regulations' => 'no', 'display_past_regulations' => 'no']): Response
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

        // the array '$map_filter_form' can be defined without the 'category' key for example, so we have to set a default value eventually
        $includePermanentAndOrTemporaryRegulations = $map_filter_form['category'] ?? 'permanents_and_temporaries';
        $includePermanentRegulations = (($includePermanentAndOrTemporaryRegulations == 'permanents_and_temporaries')
                                        or ($includePermanentAndOrTemporaryRegulations == 'permanents_only'));
        $includeTemporaryRegulations = (($includePermanentAndOrTemporaryRegulations == 'permanents_and_temporaries')
                                        or ($includePermanentAndOrTemporaryRegulations == 'temporaries_only'));
        $includeUpcomingRegulations = (($map_filter_form['display_future_regulations'] ?? 'no') != 'no');
        $includePastRegulations = (($map_filter_form['display_past_regulations'] ?? 'no') != 'no');

        $locationsAsGeoJson = $this->locationRepository->findAllForMapAsGeoJSON(
            $includePermanentRegulations,
            $includeTemporaryRegulations,
            $includeUpcomingRegulations,
            $includePastRegulations,
        );

        return new Response(
            $this->twig->render(
                name: 'map/map_filter.html.twig',
                context: [
                    'locationsAsGeoJson' => $locationsAsGeoJson,
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

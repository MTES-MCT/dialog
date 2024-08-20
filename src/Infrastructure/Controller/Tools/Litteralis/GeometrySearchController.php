<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Tools\Litteralis;

use App\Infrastructure\Controller\Tools\Litteralis\DTO\GeometrySearchDTO;
use App\Infrastructure\Form\Tools\Litteralis\GeometrySearchFormType;
use App\Infrastructure\Litteralis\LitteralisClient;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Turbo\TurboBundle;

final class GeometrySearchController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private LitteralisClient $client,
        string $melCredentials,
    ) {
        $client->setCredentials($melCredentials);
    }

    #[Route(
        '/tools/litteralis/geometry-search',
        name: 'app_tools_litteralis_geometry_search',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $dto = new GeometrySearchDTO();

        $form = $this->formFactory->create(
            type: GeometrySearchFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_tools_litteralis_geometry_search'),
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $features = $this->client->fetchAllByRegulationId($dto->arretesrcid);

            $results = [];

            foreach ($features as $feature) {
                $href = 'https://geojson.io/#data=data:application/json,' . urlencode(json_encode($feature['geometry']));

                $results[] = [
                    'href' => $href,
                    'label' => $feature['properties']['localisations'],
                ];
            }

            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return new Response(
                $this->twig->render(
                    name: 'tools/litteralis/_geometry_search.results.stream.html.twig',
                    context: [
                        'results' => $results,
                    ],
                ),
            );
        }

        return new Response(
            $this->twig->render(
                name: 'tools/litteralis/geometry_search.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

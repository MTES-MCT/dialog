<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Map;

use App\Application\DateUtilsInterface;
use App\Infrastructure\Controller\DTO\MapFilterDTO;
use App\Infrastructure\Form\Map\MapFilterFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class GetMapController
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
        name: 'app_map',
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        $dto = new MapFilterDTO($this->dateUtils->getNow());
        $form = $this->formFactory->create(
            type: MapFilterFormType::class,
            data: $dto,
            options: [
                'action' => $this->router->generate('app_map_data'),
                'method' => 'GET',
            ],
        );

        return new Response(
            $this->twig->render(
                name: 'map/map.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

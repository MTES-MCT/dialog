<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\CommandBusInterface;
use App\Infrastructure\Form\RegulationOrder\RegulationOrderType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class AddRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route(
        '/creer-une-restriction-de-circulation',
        name: 'app_regulations_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(RegulationOrderType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($form->getData());

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_list'),
                status: Response::HTTP_FOUND,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'add.html.twig',
                context: [
                    'form' => $form->createView(),
                ],
            ),
        );
    }
}

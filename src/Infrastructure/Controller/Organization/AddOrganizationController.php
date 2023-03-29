<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\SaveOrganizationCommand;
use App\Infrastructure\Form\Organization\AddOrganizationFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class AddOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route(
        '/organization/form',
        name: 'app_organization_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        $command = new SaveOrganizationCommand();
        $form = $this->formFactory->create(AddOrganizationFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_organization_list'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response($this->twig->render(
            name: 'organization/add_organization.html.twig',
            context: [
                'form' => $form->createView(),
            ],
        ),
        );
    }
}

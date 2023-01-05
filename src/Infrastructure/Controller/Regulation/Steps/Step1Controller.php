<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Infrastructure\Form\Regulation\Step1FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class Step1Controller
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route(
        '/regulations/form/{uuid}',
        name: 'app_regulations_steps_1',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid = null): Response
    {
        $form = $this->formFactory->create(Step1FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $regulationOrderUuid = $this->commandBus->handle($form->getData());

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_2', ['uuid' => $regulationOrderUuid]),
                status: Response::HTTP_FOUND,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step1.html.twig',
                context: [
                    'form' => $form->createView(),
                    'stepNumber' => 1,
                ],
            ),
        );
    }
}

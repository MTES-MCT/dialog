<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Infrastructure\Controller\Regulation\AbstractRegulationController;
use App\Infrastructure\Form\Regulation\Step1FormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

final class Step1Controller extends AbstractRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private Security $security,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/regulations/form/{uuid}',
        name: 'app_regulations_steps_1',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $uuid = null): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();
        $regulationOrderRecord = $uuid ? $this->getRegulationOrderRecord($uuid) : null;
        $command = SaveRegulationStep1Command::create($user, $regulationOrderRecord);
        $form = $this->formFactory->create(Step1FormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $regulationOrderRecord = $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_regulations_steps_2', [
                    'uuid' => $regulationOrderRecord->getUuid(),
                ]),
                status: Response::HTTP_SEE_OTHER,
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
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}

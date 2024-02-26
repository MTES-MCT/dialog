<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
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
        private Security $security,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    #[Route(
        '/regulations/add',
        name: 'app_regulation_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var SymfonyUser */
        $user = $this->security->getUser();
        $command = SaveRegulationGeneralInfoCommand::create(null, $this->dateUtils->getTomorrow());

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => $user->getOrganizations(),
                'action' => $this->router->generate('app_regulation_add'),
                'save_options' => [
                    'label' => 'common.form.continue',
                    'attr' => [
                        'class' => 'fr-btn fr-btn--icon-right fr-icon-arrow-right-line',
                    ],
                ],
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $regulationOrderRecord = $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_regulation_detail', [
                    'uuid' => $regulationOrderRecord->getUuid(),
                ]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/create.html.twig',
                context: [
                    'form' => $form->createView(),
                    'cancelUrl' => $this->router->generate('app_regulations_list'),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQuery;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Application\User\Command\MarkUserAsActiveCommand;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Infrastructure\Form\Regulation\GeneralInfoFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

final class AddRegulationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private AuthenticatedUser $authenticatedUser,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/regulations/add',
        name: 'app_regulation_add',
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var AbstractAuthenticatedUser */
        $user = $this->authenticatedUser->getSessionUser();
        $organizationUuid = current($user->getUserOrganizationUuids());
        $identifier = $this->queryBus->handle(new GetRegulationOrderIdentifierQuery($organizationUuid));

        $command = SaveRegulationGeneralInfoCommand::create(null, $identifier);

        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organizationUuid;
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));

        $form = $this->formFactory->create(
            type: GeneralInfoFormType::class,
            data: $command,
            options: [
                'organizations' => $user->getUserOrganizations(),
                'regulationOrderTemplates' => $regulationOrderTemplates,
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

            // User just created a regulation order, this is a sign of activity.
            $user = $this->authenticatedUser->getUser();
            $this->commandBus->handle(new MarkUserAsActiveCommand($user));

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

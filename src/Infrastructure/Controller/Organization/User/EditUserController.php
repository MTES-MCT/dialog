<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\SaveUserOrganizationCommand;
use App\Application\User\Query\GetOrganizationUserQuery;
use App\Domain\User\Exception\UserOrganizationNotFoundException;
use App\Infrastructure\Form\User\RolesFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;

final class EditUserController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/organizations/{organizationUuid}/users/{uuid}/edit',
        name: 'app_users_edit',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $organizationUuid, string $uuid): Response
    {
        try {
            $userOrganization = $this->queryBus->handle(new GetOrganizationUserQuery($organizationUuid, $uuid));
        } catch (UserOrganizationNotFoundException) {
            throw new NotFoundHttpException();
        }

        $organization = $userOrganization->getOrganization();
        $user = $userOrganization->getUser();

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveUserOrganizationCommand($user, $organization, $userOrganization);
        $form = $this->formFactory->create(RolesFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->handle($command);

            return new RedirectResponse(
                url: $this->router->generate('app_users_list', ['uuid' => $organizationUuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return new Response(
            content: $this->twig->render(
                name: 'organization/user/edit.html.twig',
                context: [
                    'organization' => $organization,
                    'user' => $user,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}

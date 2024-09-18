<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\SaveOrganizationUserCommand;
use App\Application\User\Query\GetOrganizationUserQuery;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Exception\OrganizationUserNotFoundException;
use App\Infrastructure\Form\User\UserFormType;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EditUserController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
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
            $organizationUser = $this->queryBus->handle(new GetOrganizationUserQuery($organizationUuid, $uuid));
        } catch (OrganizationUserNotFoundException) {
            throw new NotFoundHttpException();
        }

        $organization = $organizationUser->getOrganization();
        $user = $organizationUser->getUser();

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)
            || $organizationUser->getRole() === OrganizationRolesEnum::ROLE_ORGA_ADMIN->value) {
            throw new AccessDeniedHttpException();
        }

        $command = new SaveOrganizationUserCommand($organization, $organizationUser);
        $form = $this->formFactory->create(UserFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->handle($command);

                return new RedirectResponse(
                    url: $this->router->generate('app_users_list', ['uuid' => $organizationUuid]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (EmailAlreadyExistsException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('email.already.exists', [], 'validators')),
                );
            }
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/user/form.html.twig',
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

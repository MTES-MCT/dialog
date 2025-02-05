<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\CreateInvitationCommand;
use App\Application\User\Command\Mail\SendInvitationMailCommand;
use App\Domain\User\Exception\InvitationAlreadyExistsException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Form\User\InvitationFormType;
use App\Infrastructure\Security\AuthenticatedUser;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateInvitationController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        private CommandBusInterface $commandBus,
        private AuthenticatedUser $authenticatedUser,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{organizationUuid}/users/invite',
        name: 'app_users_invite',
        requirements: ['organizationUuid' => Requirement::UUID],
        methods: ['GET', 'POST'],
    )]
    public function __invoke(Request $request, string $organizationUuid): Response
    {
        $organization = $this->getOrganization($organizationUuid);
        $user = $this->authenticatedUser->getUser();

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $command = new CreateInvitationCommand($organization, $user);
        $form = $this->formFactory->create(InvitationFormType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $invitation = $this->commandBus->handle($command);
                $this->commandBus->dispatchAsync(new SendInvitationMailCommand($invitation));

                return new RedirectResponse(
                    url: $this->router->generate('app_users_list', ['uuid' => $organizationUuid]),
                    status: Response::HTTP_SEE_OTHER,
                );
            } catch (InvitationAlreadyExistsException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('invitation.already.exists', [], 'validators')),
                );
            } catch (OrganizationUserAlreadyExistException) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('user.already.registered', [], 'validators')),
                );
            }
        }

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/user/form.html.twig',
                context: [
                    'organization' => $organization,
                    'form' => $form->createView(),
                ],
            ),
            status: ($form->isSubmitted() && !$form->isValid())
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK,
        );
    }
}

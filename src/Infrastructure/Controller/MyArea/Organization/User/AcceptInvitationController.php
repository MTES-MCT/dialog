<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\User\Command\Invitation\JoinOrganizationCommand;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AcceptInvitationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private AuthenticatedUser $authenticatedUser,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        '/invitations/{uuid}/accept',
        name: 'app_invitation_accept',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $user = $this->authenticatedUser->getUser();

        try {
            $this->commandBus->handle(new JoinOrganizationCommand($uuid, $user));
            $session->getFlashBag()->add('success', $this->translator->trans('invitation.accept.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_my_organizations'),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (InvitationNotFoundException) {
            throw new NotFoundHttpException();
        } catch (OrganizationUserAlreadyExistException) {
            $session->getFlashBag()->add('error', $this->translator->trans('invitation.accept.error'));

            return new RedirectResponse(
                url: $this->router->generate('app_my_organizations'),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (InvitationNotOwnedException) {
            $session->getFlashBag()->add('error', $this->translator->trans('invitation.accept.error_not_owned'));

            return new RedirectResponse(
                url: $this->router->generate('app_my_organizations'),
                status: Response::HTTP_SEE_OTHER,
            );
        }
    }
}

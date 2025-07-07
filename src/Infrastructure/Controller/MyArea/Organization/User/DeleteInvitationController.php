<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\User\Command\Invitation\DeleteInvitationCommand;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\InvitationNotOwnedException;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteInvitationController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private RouterInterface $router,
        private AuthenticatedUser $authenticatedUser,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        '/invitations/{uuid}/delete',
        name: 'app_invitation_delete',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-invitation')]
    public function __invoke(Request $request, string $uuid): Response
    {
        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $user = $this->authenticatedUser->getSessionUser();

        try {
            $organizationUuid = $this->commandBus->handle(new DeleteInvitationCommand($uuid, $user));
            $session->getFlashBag()->add('success', $this->translator->trans('invitation.delete.success'));

            return new RedirectResponse(
                url: $this->router->generate('app_users_list', ['uuid' => $organizationUuid]),
                status: Response::HTTP_SEE_OTHER,
            );
        } catch (InvitationNotFoundException) {
            throw new NotFoundHttpException();
        } catch (InvitationNotOwnedException) {
            $session->getFlashBag()->add('error', $this->translator->trans('invitation.delete.error'));

            return new RedirectResponse(
                url: $this->router->generate('app_my_organizations'),
                status: Response::HTTP_SEE_OTHER,
            );
        }
    }
}

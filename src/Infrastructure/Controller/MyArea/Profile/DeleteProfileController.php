<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\DeleteUserCommand;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteProfileController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthenticatedUser $authenticatedUser,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['DELETE'])]
    #[IsCsrfTokenValid('delete-profile')]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();
        $command = new DeleteUserCommand($user);

        $this->commandBus->handle($command);

        /** @var FlashBagAwareSessionInterface */
        $session = $request->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('profile.delete.success'));

        return new RedirectResponse(
            url: $this->router->generate('app_landing'),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

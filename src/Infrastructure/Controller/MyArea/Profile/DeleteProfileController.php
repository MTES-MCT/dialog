<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Profile;

use App\Application\CommandBusInterface;
use App\Application\User\Command\DeleteUserCommand;
use App\Domain\User\Exception\UserCannotBeDeletedException;
use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class DeleteProfileController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private AuthenticatedUser $authenticatedUser,
        private RouterInterface $router,
    ) {
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['DELETE'])]
    public function __invoke(Request $request): Response
    {
        $csrfToken = new CsrfToken('deleteUser', $request->request->get('token'));

        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new BadRequestException('Invalid CSRF token');
        }

        $user = $this->authenticatedUser->getUser();
        $command = new DeleteUserCommand($user);

        try {
            $this->commandBus->handle($command);
        } catch (UserCannotBeDeletedException) {
            throw new AccessDeniedHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_landing'),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

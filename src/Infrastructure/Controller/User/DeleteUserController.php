<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\DeleteUserCommand;
use App\Application\User\Query\GetUserByUuidQuery;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\User\Exception\UserCannotBeDeletedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DeleteUserController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
    ) {
    }

    #[Route('/users/{uuid}',
        name: 'app_user_delete',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['DELETE'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        $csrfToken = new CsrfToken('deleteUser', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new BadRequestException('Invalid CSRF token');
        }

        try {
            $user = $this->queryBus->handle(new GetUserByUuidQuery($uuid));
        } catch (RegulationOrderRecordNotFoundException) {
            // The regulation may have been deleted before.
            // Don't fail, as DELETE is an idempotent method (see RFC 9110, 9.2.2).
            return new RedirectResponse(
                url: $this->router->generate('app_list_user'),
                status: Response::HTTP_SEE_OTHER,
            );
        }

        try {
            $this->commandBus->handle(new DeleteUserCommand($user));
        } catch (UserCannotBeDeletedException) {
            throw new AccessDeniedHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_list_user'),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

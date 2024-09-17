<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization\User;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Command\DeleteOrganizationUserCommand;
use App\Application\User\Query\GetOrganizationUserQuery;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Exception\OrganizationUserNotFoundException;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteOrganizationUserController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/organizations/{organizationUuid}/users/{uuid}',
        name: 'app_organization_users_delete',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-user')]
    public function __invoke(Request $request, string $organizationUuid, string $uuid): RedirectResponse
    {
        try {
            $organizationUser = $this->queryBus->handle(new GetOrganizationUserQuery($organizationUuid, $uuid));
        } catch (OrganizationUserNotFoundException) {
            throw new NotFoundHttpException();
        }

        $organization = $organizationUser->getOrganization();

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)
            || $organizationUser->getRole() === OrganizationRolesEnum::ROLE_ORGA_ADMIN->value) {
            throw new AccessDeniedHttpException();
        }

        $this->commandBus->handle(new DeleteOrganizationUserCommand($organizationUser));

        return new RedirectResponse(
            url: $this->router->generate('app_users_list', ['uuid' => $organizationUuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

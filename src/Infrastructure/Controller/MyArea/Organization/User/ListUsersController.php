<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\User;

use App\Application\QueryBusInterface;
use App\Application\User\Query\GetInvitationsQuery;
use App\Application\User\Query\GetOrganizationUsersQuery;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ListUsersController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/users',
        name: 'app_users_list',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $users = $this->queryBus->handle(new GetOrganizationUsersQuery($uuid));
        $invitations = $this->queryBus->handle(new GetInvitationsQuery($uuid));

        return new Response($this->twig->render(
            name: 'my_area/organization/user/index.html.twig',
            context: [
                'users' => $users,
                'invitations' => $invitations,
                'organization' => $organization,
            ],
        ));
    }
}

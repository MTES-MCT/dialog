<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization\User;

use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Application\User\Query\GetOrganizationUsersQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ListUsersController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
        private Security $security,
    ) {
    }

    #[Route(
        '/organizations/{uuid}/users',
        name: 'app_users_list',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($uuid));
        } catch (OrganizationNotFoundException) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(OrganizationVoter::VIEW, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $users = $this->queryBus->handle(new GetOrganizationUsersQuery($uuid));

        return new Response($this->twig->render(
            name: 'user/index.html.twig',
            context: [
                'users' => $users,
                'organization' => $organization,
            ],
        ));
    }
}

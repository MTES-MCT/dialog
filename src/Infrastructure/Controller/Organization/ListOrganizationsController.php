<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization;

use App\Infrastructure\Security\SymfonyUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ListOrganizationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private Security $security,
    ) {
    }

    #[Route(
        '/organizations',
        name: 'app_organizations_list',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        /** @var SymfonyUser|null */
        $user = $this->security->getUser();

        return new Response($this->twig->render(
            name: 'organization/index.html.twig',
            context: [
                'organizations' => $user->getUserOrganizations(),
            ],
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController
{
    public function __construct(
        private \Twig\Environment $twig,
        private Security $security,
    ) {
    }

    #[Route(
        '/organizations',
        name: 'app_my_organizations',
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        /** @var AbstractAuthenticatedUser|null */
        $user = $this->security->getUser();

        return new Response($this->twig->render(
            name: 'my_area/organization/index.html.twig',
            context: [
                'organizations' => $user->getUserOrganizations(),
            ],
        ));
    }
}

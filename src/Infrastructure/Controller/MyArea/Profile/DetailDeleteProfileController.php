<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Profile;

use App\Infrastructure\Security\AuthenticatedUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DetailDeleteProfileController
{
    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/profile/delete/detail', name: 'app_profile_delete_detail', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser->getUser();

        return new Response(
            content: $this->twig->render(
                name: '/my_area/profile/delete-profile.html.twig',
                context: [
                    'user' => $user,
                ],
            ),
        );
    }
}

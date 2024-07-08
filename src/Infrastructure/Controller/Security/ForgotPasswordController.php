<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ForgotPasswordController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            $this->twig->render('forgot-password.html.twig'),
        );
    }
}

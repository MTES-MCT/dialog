<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('contact.html.twig'));
    }
}

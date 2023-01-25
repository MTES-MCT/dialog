<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LandingController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/', name: 'app_landing', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('index.html.twig'));
    }
}

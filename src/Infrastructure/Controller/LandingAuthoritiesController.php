<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LandingAuthoritiesController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/collectivites', name: 'app_landing_authorities', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('landing_authorities.html.twig'));
    }
}

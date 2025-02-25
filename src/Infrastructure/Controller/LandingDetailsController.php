<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LandingDetailsController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/details', name: 'app_landing_details', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('details.html.twig'));
    }
}

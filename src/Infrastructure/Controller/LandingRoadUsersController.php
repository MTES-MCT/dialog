<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LandingRoadUsersController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/usagers', name: 'app_landing_road_users', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('landing_road_users.html.twig'));
    }
}

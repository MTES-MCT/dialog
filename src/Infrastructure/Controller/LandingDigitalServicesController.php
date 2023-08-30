<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LandingDigitalServicesController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/services-numeriques', name: 'app_landing_digital_services', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('landing_digital_services.html.twig'));
    }
}

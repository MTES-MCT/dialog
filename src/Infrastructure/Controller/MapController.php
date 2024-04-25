<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;

final class MapController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/carte', name: 'app_carto', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->twig->render('map.html.twig'));
    }
}

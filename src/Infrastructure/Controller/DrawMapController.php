<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/draw-map', name: 'app_draw_map')]
final class DrawMapController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(name: '_get', methods: ['GET'])]
    public function get(): Response
    {
        return new Response($this->twig->render('draw_map.html.twig'));
    }

    #[Route(name: '_post', methods: ['POST'])]
    public function post(Request $request): Response
    {
        return new Response($request->getContent());
    }
}

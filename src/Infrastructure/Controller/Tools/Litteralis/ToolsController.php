<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Tools\Litteralis;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ToolsController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/tools/litteralis',
        name: 'app_tools_litteralis',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                name: 'tools/litteralis/index.html.twig',
            ),
        );
    }
}

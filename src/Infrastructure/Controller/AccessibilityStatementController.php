<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;

final class AccessibilityStatementController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/accessibility', name: 'app_accessibility_statement', methods: ['GET'])]
    public function __invoke(): Response
    {
        $response = (new Response($this->twig->render('accessibility_statement.html.twig')))
            ->setSharedMaxAge(86400);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Annotation\Route;

final class LegalController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route('/mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function __invoke(): Response
    {
        $response = (new Response($this->twig->render('legal.html.twig')))->setSharedMaxAge(86400);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

        return $response;
    }
}

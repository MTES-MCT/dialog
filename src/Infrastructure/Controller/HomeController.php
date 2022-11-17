<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HomeController
{
    public function __construct(
        private \Twig\Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/', name: 'app_home', methods: 'GET')]
    public function __invoke(): Response
    {
        $title = $this->translator->trans('home.title');
        $context = ['title' => $title];

        return new Response($this->twig->render('index.html.twig', $context));
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class Step5Controller extends AbstractStepsController
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    #[Route(
        '/regulations/form/{uuid}/5',
        name: 'app_regulations_steps_5',
        requirements: ['uuid' => '.+'],
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step5.html.twig',
                context: [
                    'stepNumber' => 5,
                ],
            ),
        );
    }
}

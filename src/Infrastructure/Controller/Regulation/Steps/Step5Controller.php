<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Steps;

use App\Application\QueryBusInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class Step5Controller extends AbstractStepsController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
    ) {
        parent::__construct($queryBus);
    }

    #[Route(
        '/regulations/form/{uuid}/5',
        name: 'app_regulations_steps_5',
        requirements: ['uuid' => '.+'],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        return new Response(
            $this->twig->render(
                name: 'regulation/steps/step5.html.twig',
                context: [
                    'stepNumber' => 5,
                    'uuid' => $uuid,
                    'regulationOrderRecord' => $regulationOrderRecord,
                ],
            ),
        );
    }
}

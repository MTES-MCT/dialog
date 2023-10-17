<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetStatisticsQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StatisticsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/stats', name: 'app_stats', methods: ['GET'])]
    public function __invoke(): Response
    {
        $statistics = $this->queryBus->handle(new GetStatisticsQuery());

        return new Response($this->twig->render('statistics.html.twig', [
            'statistics' => $statistics,
        ]));
    }
}

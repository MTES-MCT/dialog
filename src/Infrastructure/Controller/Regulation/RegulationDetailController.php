<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderSummaryQuery;
use App\Domain\Regulation\Exception\RegulationOrderNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class RegulationDetailController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/regulations/{uuid}',
        name: 'app_regulation_detail',
        requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        try {
            $regulationOrderSummary = $this->queryBus->handle(new GetRegulationOrderSummaryQuery($uuid));
        } catch (RegulationOrderNotFoundException) {
            throw new NotFoundHttpException();
        }

        return new Response(
            $this->twig->render(
                name: 'regulation/detail.html.twig',
                context: [
                    'regulation' => $regulationOrderSummary,
                ],
            ),
        );
    }
}

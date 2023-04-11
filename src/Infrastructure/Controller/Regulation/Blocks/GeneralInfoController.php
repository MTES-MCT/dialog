<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation\Blocks;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderRecordSummaryQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GeneralInfoController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/regulations/{uuid}/general_info',
        name: 'app_regulations_general_info',
        methods: ['GET'],
    )]
    public function __invoke(Request $request, string $uuid): Response
    {
        // TODO: specific GetRegulationGeneralInfoQuery
        $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordSummaryQuery($uuid));

        return new Response(
            $this->twig->render(
                name: 'regulation/blocks/_general_info.html.twig',
                context: ['regulationOrderRecord' => $regulationOrderRecord, 'canEdit' => $regulationOrderRecord->status === 'draft'],
            ),
        );
    }
}

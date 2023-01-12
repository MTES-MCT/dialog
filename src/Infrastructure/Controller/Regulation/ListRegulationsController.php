<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationsQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ListRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/{page}', name: 'app_regulations_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function __invoke(int $page = 1): Response
    {
        $draftPagination = $this->queryBus->handle(
            new GetRegulationsQuery($page, RegulationOrderRecordStatusEnum::DRAFT),
        );
        $publishedPagination = $this->queryBus->handle(
            new GetRegulationsQuery($page, RegulationOrderRecordStatusEnum::PUBLISHED),
        );

        return new Response($this->twig->render(
            name: 'regulation/index.html.twig',
            context: [
                'draftPagination' => $draftPagination,
                'publishedPagination' => $publishedPagination,
            ],
        ));
    }
}

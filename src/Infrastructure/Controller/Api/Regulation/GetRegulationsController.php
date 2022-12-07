<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulation;

use App\Application\QueryBusInterface;
use App\Application\RegulationOrder\Query\GetAllRegulationOrderListItemsQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/regulations.{_format}',
        methods: 'GET',
        name: 'app_regulations',
        requirements: ['_format' => 'xml'],
    )]
    public function __invoke(): Response
    {
        $regulationOrders = $this->queryBus->handle(new GetAllRegulationOrderListItemsQuery());

        return new Response(
            $this->twig->render('api/regulation/index.xml.twig', [
                'regulationOrders' => $regulationOrders,
            ]),
        );
    }
}

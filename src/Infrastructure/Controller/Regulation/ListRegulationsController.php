<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetAllRegulationOrderListItemsQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ListRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/', name: 'app_regulations_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        $regulationOrders = $this->queryBus->handle(new GetAllRegulationOrderListItemsQuery());

        return new Response($this->twig->render(
            name: 'regulation/index.html.twig',
            context: [
                'regulationOrders' => $regulationOrders,
            ],
        ));
    }
}

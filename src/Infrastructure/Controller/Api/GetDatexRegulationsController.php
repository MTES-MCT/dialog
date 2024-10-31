<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetDatexRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/regulations.{_format}',
        methods: 'GET',
        name: 'api_regulations_datex',
        requirements: ['_format' => 'xml'],
    )]
    public function __invoke(): Response
    {
        $regulationOrders = $this->queryBus->handle(new GetRegulationOrdersToDatexFormatQuery());

        return new Response(
            $this->twig->render('api/datex_regulations.xml.twig', [
                'publicationTime' => $this->dateUtils->getNow(),
                'regulationOrders' => $regulationOrders,
            ]),
        );
    }
}

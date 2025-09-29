<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/regulations.xml',
        methods: 'GET',
        name: 'api_regulations_list',
    )]
    #[OA\Tag(name: 'Regulations')]
    public function __invoke(): Response
    {
        $regulationOrders = $this->queryBus->handle(new GetRegulationOrdersToDatexFormatQuery());

        return new Response(
            $this->twig->render('api/regulations.xml.twig', [
                'publicationTime' => $this->dateUtils->getNow(),
                'regulationOrders' => $regulationOrders,
            ]),
        );
    }
}

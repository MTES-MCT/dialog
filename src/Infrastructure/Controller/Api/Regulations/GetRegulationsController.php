<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        '/api/regulations.{_format}',
        methods: 'GET',
        name: 'api_regulations_list',
        defaults: ['_format' => 'xml'],
    )]
    #[OA\Tag(name: 'Regulations')]
    public function __invoke(
        #[MapQueryParameter]
        bool $includePermanent = true,
        #[MapQueryParameter]
        bool $includeTemporary = true,
        #[MapQueryParameter]
        bool $includeExpired = false,
    ): Response {
        $regulationOrders = $this->queryBus->handle(
            new GetRegulationOrdersToDatexFormatQuery(
                includePermanent: $includePermanent,
                includeTemporary: $includeTemporary,
                includeExpired: $includeExpired,
            ),
        );

        return new Response(
            $this->twig->render('api/regulations.xml.twig', [
                'publicationTime' => $this->dateUtils->getNow(),
                'regulationOrders' => $regulationOrders,
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml; charset=UTF-8'],
        );
    }
}

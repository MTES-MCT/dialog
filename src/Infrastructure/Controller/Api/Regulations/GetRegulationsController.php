<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\DatexGeneratorInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class GetRegulationsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
        private DatexGeneratorInterface $datexGenerator,
    ) {
    }

    #[Route(
        '/api/regulations.{_format}',
        methods: ['GET', 'HEAD'],
        name: 'api_regulations_list',
        defaults: ['_format' => 'xml'],
    )]
    #[OA\Tag(name: 'Public')]
    public function __invoke(
        Request $request,
        #[MapQueryParameter]
        bool $includePermanent = true,
        #[MapQueryParameter]
        bool $includeTemporary = true,
        #[MapQueryParameter]
        bool $includeExpired = false,
    ): Response {
        if ($request->isMethod('HEAD')) {
            return new Response(
                '',
                Response::HTTP_OK,
                [
                    'Content-Type' => 'text/xml; charset=UTF-8',
                    'Content-Length' => (string) $this->datexGenerator->getCachedDatexSize(),
                ],
            );
        }

        $isDefaultParams = $includePermanent && $includeTemporary && !$includeExpired;

        if ($isDefaultParams) {
            return new Response(
                $this->datexGenerator->getCachedDatex(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'text/xml; charset=UTF-8',
                    'Content-Length' => (string) $this->datexGenerator->getCachedDatexSize(),
                ],
            );
        }

        $regulationOrders = $this->queryBus->handle(
            new GetRegulationOrdersToDatexFormatQuery(
                includePermanent: $includePermanent,
                includeTemporary: $includeTemporary,
                includeExpired: $includeExpired,
            ),
        );

        return new StreamedResponse(
            function () use ($regulationOrders): void {
                $this->twig->display('api/regulations.xml.twig', [
                    'publicationTime' => $this->dateUtils->getNow(),
                    'regulationOrders' => $regulationOrders,
                ]);
            },
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml; charset=UTF-8'],
        );
    }
}

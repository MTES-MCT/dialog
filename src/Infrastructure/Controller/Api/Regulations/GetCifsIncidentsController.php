<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Regulations;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetCifsIncidentsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetCifsIncidentsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/regulations/cifs.{_format}',
        methods: 'GET',
        name: 'api_regulations_cifs',
        defaults: ['_format' => 'xml'],
    )]
    #[OA\Tag(name: 'Public')]
    public function __invoke(): Response
    {
        $incidents = $this->queryBus->handle(new GetCifsIncidentsQuery());

        return new Response(
            $this->twig->render('api/regulations/cifs.xml.twig', [
                'incidents' => $incidents,
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml; charset=UTF-8'],
        );
    }
}

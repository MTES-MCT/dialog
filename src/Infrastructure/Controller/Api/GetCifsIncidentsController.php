<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetCifsIncidentsQuery;
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
        requirements: ['_format' => 'xml'],
    )]
    public function __invoke(): Response
    {
        $incidents = $this->queryBus->handle(new GetCifsIncidentsQuery());

        return new Response(
            $this->twig->render('api/regulations/cifs.xml.twig', [
                'incidents' => $incidents,
            ]),
        );
    }
}

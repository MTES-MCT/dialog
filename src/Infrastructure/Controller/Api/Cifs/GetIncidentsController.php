<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Cifs;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersAsCifsIncidentsQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GetIncidentsController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/api/cifs/incidents.{_format}',
        methods: 'GET',
        name: 'api_cifs_incidents',
        requirements: ['_format' => 'xml'],
    )]
    public function __invoke(): Response
    {
        $incidents = $this->queryBus->handle(new GetRegulationOrdersAsCifsIncidentsQuery());

        return new Response(
            $this->twig->render('api/cifs/incidents.xml.twig', [
                'incidents' => $incidents,
            ]),
        );
    }
}

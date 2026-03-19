<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Application\Organization\ApiClient\Query\GetOrganizationApiClientsQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ListApiClientsController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/api-clients',
        name: 'app_config_api_clients_list',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $apiClients = $this->queryBus->handle(new GetOrganizationApiClientsQuery($uuid));

        return new Response($this->twig->render(
            name: 'my_area/organization/api_client/index.html.twig',
            context: [
                'organization' => $organization,
                'apiClients' => $apiClients,
            ],
        ));
    }
}

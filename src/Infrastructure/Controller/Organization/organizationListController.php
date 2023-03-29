<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization;

use App\Application\Organization\Query\GetOrganizationsQuery;
use App\Application\QueryBusInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class organizationListController
{
    public function __construct(
        private \Twig\Environment $twig,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route(
        '/organizations',
        name: 'app_organization_list',
        methods: ['GET'],
    )]
    public function __invoke()
    {
        $organizations = $this->queryBus->handle(
            new GetOrganizationsQuery());

        return new Response($this->twig->render(
            name: 'organization/index.html.twig',
            context: ['organizations' => $organizations],
        ));
    }
}

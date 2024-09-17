<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Organization\Visa;

use App\Application\QueryBusInterface;
use App\Application\Visa\Query\GetOrganizationVisaModelsQuery;
use App\Infrastructure\Controller\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ListVisaModelsController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/visas',
        name: 'app_config_visas_list',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $visaModels = $this->queryBus->handle(new GetOrganizationVisaModelsQuery($uuid));

        return new Response($this->twig->render(
            name: 'organization/visa_model/index.html.twig',
            context: [
                'visaModels' => $visaModels,
                'organization' => $organization,
            ],
        ));
    }
}

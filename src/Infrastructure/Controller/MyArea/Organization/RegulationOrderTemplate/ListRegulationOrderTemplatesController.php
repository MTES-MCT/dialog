<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class ListRegulationOrderTemplatesController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/regulation_order_templates',
        name: 'app_config_regulation_order_templates_list',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = $organization->getUuid();
        $regulationOrderTemplates = $this->queryBus->handle(new GetRegulationOrderTemplatesQuery($dto));

        return new Response($this->twig->render(
            name: 'my_area/organization/regulation_order_template/index.html.twig',
            context: [
                'regulationOrderTemplates' => $regulationOrderTemplates,
                'organization' => $organization,
            ],
        ));
    }
}

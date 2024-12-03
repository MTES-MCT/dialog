<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization;

use App\Application\QueryBusInterface;
use App\Application\StorageInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class OrganizationDetailController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        private StorageInterface $storage,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}',
        name: 'app_config_organization_detail',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $logo = $organization->getLogo() ? $this->storage->getUrl($organization->getLogo()) : null;

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/detail.html.twig',
                context: [
                    'organization' => $organization,
                    'logo' => $logo,
                ],
            ),
        );
    }
}

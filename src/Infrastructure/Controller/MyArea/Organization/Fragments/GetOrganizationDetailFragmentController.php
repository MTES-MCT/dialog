<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\Fragments;

use App\Application\QueryBusInterface;
use App\Application\StorageInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetOrganizationDetailFragmentController extends AbstractOrganizationController
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
        '/_fragment/organizations/{uuid}/preview',
        name: 'fragment_organizations_preview',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $logo = $organization->getLogo() ? $this->storage->getUrl($organization->getLogo()) : null;

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/fragments/_preview.html.twig',
                context: [
                    'organization' => $organization,
                    'logo' => $logo,
                ],
            ),
            status: Response::HTTP_OK,
        );
    }
}

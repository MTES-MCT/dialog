<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\SigningAuthority\Fragments;

use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class GetSigningAuthorityFragmentController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/_fragment/organizations/{uuid}/signing_authority/preview',
        name: 'fragment_organizations_signing_authority_preview',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $organization = $this->getOrganization($uuid);
        $signingAuthority = $this->queryBus->handle(new GetSigningAuthorityByOrganizationQuery($uuid));

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/signing_authority/fragments/_preview.html.twig',
                context: [
                    'organization' => $organization,
                    'signingAuthority' => $signingAuthority,
                ],
            ),
            status: Response::HTTP_OK,
        );
    }
}

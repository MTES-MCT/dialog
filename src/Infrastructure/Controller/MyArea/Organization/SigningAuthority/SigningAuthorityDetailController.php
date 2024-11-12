<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\SigningAuthority;

use App\Application\Organization\SigningAuthority\Query\GetSigningAuthorityByOrganizationQuery;
use App\Application\QueryBusInterface;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class SigningAuthorityDetailController extends AbstractOrganizationController
{
    public function __construct(
        private \Twig\Environment $twig,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/signing_authority',
        name: 'app_config_signing_authority_detail',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function __invoke(string $uuid): Response
    {
        $signingAuthority = $this->queryBus->handle(new GetSigningAuthorityByOrganizationQuery($uuid));
        $organization = $this->getOrganization($uuid);

        return new Response(
            content: $this->twig->render(
                name: 'my_area/organization/signing_authority/detail.html.twig',
                context: [
                    'signingAuthority' => $signingAuthority,
                    'organization' => $organization,
                ],
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization;

use App\Application\CommandBusInterface;
use App\Application\Organization\Logo\Command\DeleteOrganizationLogoCommand;
use App\Application\QueryBusInterface;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteOrganizationLogoController extends AbstractOrganizationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
    ) {
        parent::__construct($queryBus, $security);
    }

    #[Route(
        '/organizations/{uuid}/logo/delete',
        name: 'app_config_organization_delete_logo',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-logo')]
    public function __invoke(string $uuid): RedirectResponse
    {
        $organization = $this->getOrganization($uuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        $this->commandBus->handle(new DeleteOrganizationLogoCommand($organization));

        return new RedirectResponse(
            url: $this->router->generate('app_config_organization_edit_logo', ['uuid' => $uuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\RegulationOrderTemplate\DeleteRegulationOrderTemplateCommand;
use App\Domain\Regulation\Exception\RegulationOrderTemplateCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderTemplateNotFoundException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteRegulationOrderTemplateController extends AbstractOrganizationController
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
        '/organizations/{organizationUuid}/regulation_order_templates/{uuid}',
        name: 'app_config_regulation_order_templates_delete',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-regulation-order-template')]
    public function __invoke(string $organizationUuid, string $uuid): RedirectResponse
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->commandBus->handle(new DeleteRegulationOrderTemplateCommand($uuid));
        } catch (RegulationOrderTemplateNotFoundException|RegulationOrderTemplateCannotBeDeletedException) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_config_regulation_order_templates_list', ['uuid' => $organizationUuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

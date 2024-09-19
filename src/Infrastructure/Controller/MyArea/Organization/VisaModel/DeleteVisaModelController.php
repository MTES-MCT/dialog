<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\VisaModel;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\VisaModel\Command\DeleteVisaModelCommand;
use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Infrastructure\Controller\MyArea\Organization\AbstractOrganizationController;
use App\Infrastructure\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteVisaModelController extends AbstractOrganizationController
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
        '/organizations/{organizationUuid}/visa_models/{uuid}',
        name: 'app_config_visa_models_delete',
        requirements: ['organizationUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-visa-model')]
    public function __invoke(string $organizationUuid, string $uuid): RedirectResponse
    {
        $organization = $this->getOrganization($organizationUuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->commandBus->handle(new DeleteVisaModelCommand($uuid));
        } catch (VisaModelNotFoundException) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_config_visa_models_list', ['uuid' => $organizationUuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

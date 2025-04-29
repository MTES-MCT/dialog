<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Application\CommandBusInterface;
use App\Application\Organization\MailingList\Command\DeleteMailingListCommand;
use App\Application\QueryBusInterface;
use App\Domain\Organization\VisaModel\Exception\VisaModelCannotBeDeletedException;
use App\Domain\Organization\VisaModel\Exception\VisaModelNotFoundException;
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

final class DeleteRecipientController extends AbstractOrganizationController
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
        '/organizations/{uuid}/recipients/{mailingListUuid}',
        name: 'app_config_mailing_list_delete',
        requirements: ['mailingListUuid' => Requirement::UUID, 'uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-mailing-list')]
    public function __invoke(string $mailingListUuid, string $uuid): RedirectResponse
    {
        $organization = $this->getOrganization($uuid);

        if (!$this->security->isGranted(OrganizationVoter::EDIT, $organization)) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->commandBus->handle(new DeleteMailingListCommand($mailingListUuid));
        } catch (VisaModelNotFoundException|VisaModelCannotBeDeletedException) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse(
            url: $this->router->generate('app_config_recipients_list', ['uuid' => $uuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

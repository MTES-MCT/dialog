<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Regulation;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationOrderStorageCommand;
use App\Application\Regulation\Query\GetStorageRegulationOrderQuery;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Infrastructure\Security\Voter\RegulationOrderRecordVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class DeleteStorageRegulationOrderController extends AbstractRegulationController
{
    public function __construct(
        private RouterInterface $router,
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        Security $security,
        CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
        parent::__construct($queryBus, $security, $canOrganizationAccessToRegulation);
    }

    #[Route(
        '/regulations/{uuid}/storage/delete',
        name: 'app_config_regulation_delete_storage',
        requirements: ['uuid' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    #[IsCsrfTokenValid('delete-storage')]
    public function __invoke(string $uuid): RedirectResponse
    {
        $regulationOrderRecord = $this->getRegulationOrderRecord($uuid);

        if (!$this->security->isGranted(RegulationOrderRecordVoter::PUBLISH, $regulationOrderRecord)) {
            throw new AccessDeniedHttpException();
        }

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $storageRegulationOrder = $this->queryBus->handle(new GetStorageRegulationOrderQuery($regulationOrder));

        $this->commandBus->handle(new DeleteRegulationOrderStorageCommand($storageRegulationOrder));

        return new RedirectResponse(
            url: $this->router->generate('app_regulation_detail', ['uuid' => $uuid]),
            status: Response::HTTP_SEE_OTHER,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;

final class DeleteRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DeleteRegulationCommand $command): void
    {
        $regulationOrderRecord = $command->regulationOrderRecord;
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        if (false === $this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $command->userOrganizationUuids)) {
            throw new RegulationOrderRecordCannotBeDeletedException();
        }

        $this->commandBus->handle(new CreateRegulationOrderHistoryCommand($regulationOrder, ActionTypeEnum::DELETE->value));

        $this->regulationOrderRepository->delete($regulationOrder);
    }
}

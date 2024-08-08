<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;

final class DeleteRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
    ) {
    }

    public function __invoke(DeleteRegulationCommand $command): void
    {
        $regulationOrderRecord = $command->regulationOrderRecord;

        if (false === $this->canOrganizationAccessToRegulation->isSatisfiedBy($regulationOrderRecord, $command->organizationUserUuids)) {
            throw new RegulationOrderRecordCannotBeDeletedException();
        }

        $this->regulationOrderRepository->delete($regulationOrderRecord->getRegulationOrder());
    }
}

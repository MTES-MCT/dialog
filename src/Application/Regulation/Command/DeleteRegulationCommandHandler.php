<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteRegulationOrderRecord;

final class DeleteRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private CanDeleteRegulationOrderRecord $canDeleteRegulationOrderRecord,
    ) {
    }

    public function __invoke(DeleteRegulationCommand $command): void
    {
        $regulationOrderRecord = $command->regulationOrderRecord;

        if (false === $this->canDeleteRegulationOrderRecord->isSatisfiedBy($command->organization, $regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBeDeletedException();
        }

        $this->regulationOrderRepository->delete($regulationOrderRecord->getRegulationOrder());
    }
}

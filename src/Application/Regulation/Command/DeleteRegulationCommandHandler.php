<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Specification\CanUserDeleteRegulationOrderRecord;

final class DeleteRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private CanUserDeleteRegulationOrderRecord $canUserDeleteRegulationOrderRecord,
    ) {
    }

    public function __invoke(DeleteRegulationCommand $command): string
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneByUuid($command->uuid);
        
        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            throw new RegulationOrderRecordNotFoundException();
        }

        if (false === $this->canUserDeleteRegulationOrderRecord->isSatisfiedBy($command->userOrganizationUuids, $regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBeDeletedException();
        }

        $status = $regulationOrderRecord->getStatus();
        $this->regulationOrderRecordRepository->delete($regulationOrderRecord);

        return $status;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class DeleteRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(DeleteRegulationCommand $command): void
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneByUuid($command->uuid);

        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            return;
        }

        $this->regulationOrderRecordRepository->delete($regulationOrderRecord);
    }
}

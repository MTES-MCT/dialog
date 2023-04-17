<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

// TODO : Verify locations
final class PublishRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(PublishRegulationCommand $command): void
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository
            ->findOneByUuid($command->regulationOrderRecordUuid);

        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            throw new RegulationOrderRecordNotFoundException();
        }

        $regulationOrderRecord->updateStatus($command->status);
    }
}

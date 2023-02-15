<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;

final class PublishRegulationCommandHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
    ) {
    }

    public function __invoke(PublishRegulationCommand $command): void
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository
            ->findOneByUuid($command->regulationOrderRecordUuid);

        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            throw new RegulationOrderRecordNotFoundException();
        }

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED &&
            false === $this->canRegulationOrderRecordBePublished->isSatisfiedBy($regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBePublishedException();
        }

        $regulationOrderRecord->updateStatus($command->status);
    }
}

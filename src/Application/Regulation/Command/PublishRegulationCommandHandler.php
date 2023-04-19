<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordCannotBePublishedException;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;

// TODO : Verify locations
final class PublishRegulationCommandHandler
{
    public function __construct(
        private CanRegulationOrderRecordBePublished $canRegulationOrderRecordBePublished,
    ) {
    }

    public function __invoke(PublishRegulationCommand $command): void
    {
        if (false === $this->canRegulationOrderRecordBePublished->isSatisfiedBy($command->regulationOrderRecord)) {
            throw new RegulationOrderRecordCannotBePublishedException();
        }

        $command->regulationOrderRecord->updateStatus(RegulationOrderRecordStatusEnum::PUBLISHED);
    }
}

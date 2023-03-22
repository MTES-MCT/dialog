<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class SaveRegulationStep1CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private \DateTimeInterface $now,
    ) {
    }

    public function __invoke(SaveRegulationStep1Command $command): RegulationOrderRecord
    {
        // If submitting step 1 for the first time, we create the regulationOrder and regulationOrderRecord
        if (!$command->regulationOrderRecord instanceof RegulationOrderRecord) {
            $regulationOrder = $this->regulationOrderRepository->save(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    issuingAuthority: $command->issuingAuthority,
                    description: $command->description,
                    startDate: $command->startDate,
                    endDate: $command->endDate,
                ),
            );

            $regulationOrderRecord = $this->regulationOrderRecordRepository->save(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    status: RegulationOrderRecordStatusEnum::DRAFT,
                    regulationOrder: $regulationOrder,
                    createdAt: $this->now,
                    organization: $command->organization,
                ),
            );

            return $regulationOrderRecord;
        }

        $command->regulationOrderRecord->getRegulationOrder()->update(
            issuingAuthority: $command->issuingAuthority,
            description: $command->description,
            startDate: $command->startDate,
            endDate: $command->endDate,
        );

        return $command->regulationOrderRecord;
    }
}

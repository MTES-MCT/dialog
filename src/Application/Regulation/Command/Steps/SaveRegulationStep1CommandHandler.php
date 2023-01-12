<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class SaveRegulationStep1CommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationConditionRepositoryInterface $regulationConditionRepository,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private \DateTimeInterface $now,
    ) {
    }

    public function __invoke(SaveRegulationStep1Command $command): string
    {
        // If submitting step 1 for the first time, we create the regulationCondition, regulationOrder and regulationOrderRecord
        if (!$command->regulationOrderRecord instanceof RegulationOrderRecord) {
            $regulationCondition = $this->regulationConditionRepository->save(
                new RegulationCondition(
                    uuid: $this->idFactory->make(),
                    negate: false,
                ),
            );

            $regulationOrder = $this->regulationOrderRepository->save(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    description: $command->description,
                    issuingAuthority: $command->issuingAuthority,
                    regulationCondition: $regulationCondition,
                ),
            );

            $regulationOrderRecord = $this->regulationOrderRecordRepository->save(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    status: RegulationOrderRecordStatusEnum::DRAFT,
                    lastFilledStep: 1,
                    regulationOrder: $regulationOrder,
                    createdAt: $this->now,
                ),
            );

            return $regulationOrderRecord->getUuid();
        }

        $command->regulationOrderRecord->getRegulationOrder()->update(
            description: $command->description,
            issuingAuthority: $command->issuingAuthority,
        );

        return $command->regulationOrderRecord->getUuid();
    }
}

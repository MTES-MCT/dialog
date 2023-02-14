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

    public function __invoke(SaveRegulationStep1Command $command): RegulationOrder
    {
        // If submitting step 1 for the first time, we create the regulationOrderRecord, regulationCondition and regulationOrder
        if (!$command->regulationOrder instanceof RegulationOrder) {
            $regulationOrderRecord = $this->regulationOrderRecordRepository->save(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    status: RegulationOrderRecordStatusEnum::DRAFT,
                    createdAt: $this->now,
                    organization: $command->organization,
                ),
            );

            $regulationCondition = $this->regulationConditionRepository->save(
                new RegulationCondition(
                    uuid: $this->idFactory->make(),
                    negate: false,
                ),
            );

            $regulationOrder = $this->regulationOrderRepository->save(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    issuingAuthority: $command->issuingAuthority,
                    description: $command->description,
                    regulationOrderRecord: $regulationOrderRecord,
                    regulationCondition: $regulationCondition,
                ),
            );

            return $regulationOrder;
        }

        $command->regulationOrder->update(
            issuingAuthority: $command->issuingAuthority,
            description: $command->description,
        );

        return $command->regulationOrder;
    }
}

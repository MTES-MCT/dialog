<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Organization\VisaModel\Query\GetVisaModelQuery;
use App\Application\QueryBusInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class SaveRegulationGeneralInfoCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private \DateTimeInterface $now,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(SaveRegulationGeneralInfoCommand $command): RegulationOrderRecord
    {
        $command->cleanOtherObjectText();
        $visaModel = $command->visaModelUuid
            ? $this->queryBus->handle(new GetVisaModelQuery($command->visaModelUuid))
            : null;

        // If submitting the form the first time, we create the regulationOrder and regulationOrderRecord
        if (!$command->regulationOrderRecord instanceof RegulationOrderRecord) {
            $regulationOrder = $this->regulationOrderRepository->add(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    identifier: $command->identifier,
                    category: $command->category,
                    object: $command->object,
                    description: $command->description,
                    otherObjectText: $command->otherObjectText,
                    additionalVisas: $command->additionalVisas,
                    additionalReasons: $command->otherObjectText,
                    visaModel: $visaModel,
                ),
            );

            $regulationOrderRecord = $this->regulationOrderRecordRepository->add(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    source: $command->source,
                    status: RegulationOrderRecordStatusEnum::DRAFT->value,
                    regulationOrder: $regulationOrder,
                    createdAt: $this->now,
                    organization: $command->organization,
                ),
            );

            return $regulationOrderRecord;
        }

        $command->regulationOrderRecord->updateOrganization($command->organization);
        $command->regulationOrderRecord->getRegulationOrder()->update(
            identifier: $command->identifier,
            category: $command->category,
            object: $command->object,
            description: $command->description,
            otherObjectText: $command->otherObjectText,
            additionalVisas: $command->additionalVisas,
            additionalReasons: $command->additionalReasons,
            visaModel: $visaModel,
        );otherObjectText

        return $command->regulationOrderRecord;
    }
}

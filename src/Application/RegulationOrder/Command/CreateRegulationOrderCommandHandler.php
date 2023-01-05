<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationConditionRepositoryInterface $regulationConditionRepository,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private OverallPeriodRepositoryInterface $overallPeriodRepository,
        private VehicleCharacteristicsRepositoryInterface $vehicleCharacteristicsRepository,
    ) {
    }

    public function __invoke(CreateRegulationOrderCommand $command): string
    {
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

        $this->overallPeriodRepository->save(
            new OverallPeriod(
                uuid: $this->idFactory->make(),
                regulationCondition: $regulationCondition,
                startPeriod: $command->startPeriod,
                endPeriod: $command->endPeriod,
            ),
        );

        $this->vehicleCharacteristicsRepository->save(
            new VehicleCharacteristics(
                uuid: $this->idFactory->make(),
                regulationCondition: $regulationCondition,
                maxWeight: $command->maxWeight,
                maxHeight: $command->maxHeight,
                maxWidth: $command->maxWidth,
                maxLength: $command->maxLength,
            ),
        );

        return $regulationOrder->getUuid();
    }
}

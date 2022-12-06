<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;

final class CreateRegulationOrderCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationConditionRepositoryInterface $regulationConditionRepository,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
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

        return $regulationOrder->getUuid();
    }
}

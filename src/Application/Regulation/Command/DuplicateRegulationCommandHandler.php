<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetDuplicateIdentifierQuery;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

final class DuplicateRegulationCommandHandler
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateRegulationCommand $command): RegulationOrderRecord
    {
        $organization = $command->originalRegulationOrderRecord->getOrganization();
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($organization, $originalRegulationOrder);
        $this->duplicateRegulationMeasures($originalRegulationOrder, $duplicatedRegulationOrderRecord);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        Organization $organization,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $identifier = $this->queryBus->handle(
            new GetDuplicateIdentifierQuery($originalRegulationOrder->getIdentifier(), $organization),
        );

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->category = $originalRegulationOrder->getCategory();
        $generalInfo->otherCategoryText = $originalRegulationOrder->getOtherCategoryText();
        $generalInfo->organization = $organization;
        $generalInfo->identifier = $identifier;
        $generalInfo->title = $originalRegulationOrder->getTitle();
        $generalInfo->additionalVisas = $originalRegulationOrder->getAdditionalVisas();
        $generalInfo->additionalReasons = $originalRegulationOrder->getAdditionalReasons();
        $generalInfo->visaModelUuid = $originalRegulationOrder->getVisaModel()?->getUuid();

        return $this->commandBus->handle($generalInfo);
    }

    private function duplicateRegulationMeasures(
        RegulationOrder $originalRegulationOrder,
        RegulationOrderRecord $duplicatedRegulationOrderRecord,
    ): void {
        if (!empty($originalRegulationOrder->getMeasures())) {
            foreach ($originalRegulationOrder->getMeasures() as $measure) {
                $this->commandBus->handle(new DuplicateMeasureCommand($measure, $duplicatedRegulationOrderRecord));
            }
        }
    }
}

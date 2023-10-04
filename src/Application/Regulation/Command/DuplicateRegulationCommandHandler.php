<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandler
{
    public function __construct(
        private TranslatorInterface $translator,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateRegulationCommand $command): RegulationOrderRecord
    {
        $organization = $command->originalRegulationOrderRecord->getOrganization();
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($organization, $originalRegulationOrder);
        $this->duplicateRegulationLocations($originalRegulationOrder, $duplicatedRegulationOrderRecord);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        Organization $organization,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->category = $originalRegulationOrder->getCategory();
        $generalInfo->otherCategoryText = $originalRegulationOrder->getOtherCategoryText();
        $generalInfo->organization = $organization;
        $generalInfo->identifier = $this->translator->trans('regulation.identifier.copy', [
            '%identifier%' => $originalRegulationOrder->getIdentifier(),
        ]);
        $generalInfo->description = $originalRegulationOrder->getDescription();
        $generalInfo->startDate = $originalRegulationOrder->getStartDate();
        $generalInfo->endDate = $originalRegulationOrder->getEndDate();

        return $this->commandBus->handle($generalInfo);
    }

    private function duplicateRegulationLocations(
        RegulationOrder $originalRegulationOrder,
        RegulationOrderRecord $duplicatedRegulationOrderRecord,
    ): void {
        foreach ($originalRegulationOrder->getLocations() as $location) {
            $locationCommand = new SaveRegulationLocationCommand($duplicatedRegulationOrderRecord);
            $locationCommand->address = $location->getAddress();
            $locationCommand->fromHouseNumber = $location->getFromHouseNumber();
            $locationCommand->toHouseNumber = $location->getToHouseNumber();

            if (!empty($location->getMeasures())) {
                foreach ($location->getMeasures() as $measure) {
                    $periodCommands = [];

                    foreach ($measure->getPeriods() as $period) {
                        $cmd = new SavePeriodCommand();
                        $cmd->startTime = $period->getStartTime();
                        $cmd->endTime = $period->getEndTime();
                        $periodCommands[] = $cmd;
                    }

                    $vehicleSetCommand = $measure->getVehicleSet()
                        ? (new SaveVehicleSetCommand())->initFromEntity($measure->getVehicleSet())
                        : null;

                    $measureCommand = new SaveMeasureCommand();
                    $measureCommand->type = $measure->getType();
                    $measureCommand->createdAt = $measure->getCreatedAt();
                    $measureCommand->vehicleSet = $vehicleSetCommand;
                    $measureCommand->periods = $periodCommands;
                    $locationCommand->measures[] = $measureCommand;
                }
            }

            $this->commandBus->handle($locationCommand);
        }
    }
}

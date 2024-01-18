<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
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
            $locationCommand->roadType = $location->getRoadType();
            $locationCommand->cityCode = $location->getCityCode();
            $locationCommand->cityLabel = $location->getCityLabel();
            $locationCommand->administrator = $location->getAdministrator();
            $locationCommand->roadNumber = $location->getRoadNumber();
            $locationCommand->roadName = $location->getRoadNAme();
            $locationCommand->fromHouseNumber = $location->getFromHouseNumber();
            $locationCommand->toHouseNumber = $location->getToHouseNumber();
            $locationCommand->geometry = $location->getGeometry();

            if (!empty($location->getMeasures())) {
                foreach ($location->getMeasures() as $measure) {
                    $periodCommands = [];

                    foreach ($measure->getPeriods() as $period) {
                        $cmd = new SavePeriodCommand();
                        $cmd->startDate = $period->getStartDateTime();
                        $cmd->startTime = $period->getStartDateTime();
                        $cmd->endDate = $period->getEndDateTime();
                        $cmd->endTime = $period->getEndDateTime();
                        $cmd->recurrenceType = $period->getRecurrenceType();

                        $dailyRange = $period->getDailyRange();
                        if ($dailyRange) {
                            $dailyRangeCommand = (new SaveDailyRangeCommand())->initFromEntity($dailyRange);
                            $cmd->dailyRange = $dailyRangeCommand;
                        }

                        $timeSlotCommands = [];
                        if ($period->getTimeSlots()) {
                            foreach ($period->getTimeSlots() as $timeSlot) {
                                $timeSlotCommands[] = (new SaveTimeSlotCommand())->initFromEntity($timeSlot);
                            }
                        }

                        $cmd->timeSlots = $timeSlotCommands;
                        $periodCommands[] = $cmd;
                    }

                    $vehicleSetCommand = $measure->getVehicleSet()
                        ? (new SaveVehicleSetCommand())->initFromEntity($measure->getVehicleSet())
                        : null;

                    $measureCommand = new SaveMeasureCommand();
                    $measureCommand->type = $measure->getType();
                    $measureCommand->createdAt = $measure->getCreatedAt();
                    $measureCommand->maxSpeed = $measure->getMaxSpeed();
                    $measureCommand->vehicleSet = $vehicleSetCommand;
                    $measureCommand->periods = $periodCommands;
                    $locationCommand->measures[] = $measureCommand;
                }
            }

            $this->commandBus->handle($locationCommand);
        }
    }
}

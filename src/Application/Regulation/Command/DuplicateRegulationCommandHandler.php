<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
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
        $this->duplicateRegulationMeasures($originalRegulationOrder, $duplicatedRegulationOrderRecord);

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

    private function duplicateRegulationMeasures(
        RegulationOrder $originalRegulationOrder,
        RegulationOrderRecord $duplicatedRegulationOrderRecord,
    ): void {
        if (!empty($originalRegulationOrder->getMeasures())) {
            foreach ($originalRegulationOrder->getMeasures() as $measure) {
                $periodCommands = [];
                $locationCommands = [];

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

                foreach ($measure->getLocations() as $location) {
                    $cmd = new SaveLocationCommand();
                    $cmd->roadType = $location->getRoadType();
                    $cmd->administrator = $location->getAdministrator();
                    $cmd->roadNumber = $location->getRoadNumber();
                    $cmd->cityCode = $location->getCityCode();
                    $cmd->cityLabel = $location->getCityLabel();
                    $cmd->roadName = $location->getRoadName();
                    $cmd->fromHouseNumber = $location->getFromHouseNumber();
                    $cmd->toHouseNumber = $location->getToHouseNumber();
                    $cmd->geometry = $location->getGeometry();
                    $cmd->fullLaneGeometry = $location?->getFullLaneGeometry();
                    $cmd->measure = $measure;

                    $locationCommands[] = $cmd;
                }

                $vehicleSetCommand = $measure->getVehicleSet()
                    ? (new SaveVehicleSetCommand())->initFromEntity($measure->getVehicleSet())
                    : null;

                $measureCommand = new SaveMeasureCommand($duplicatedRegulationOrderRecord->getRegulationOrder());
                $measureCommand->type = $measure->getType();
                $measureCommand->createdAt = $measure->getCreatedAt();
                $measureCommand->maxSpeed = $measure->getMaxSpeed();
                $measureCommand->vehicleSet = $vehicleSetCommand;
                $measureCommand->periods = $periodCommands;
                $measureCommand->locations = $locationCommands;

                $this->commandBus->handle($measureCommand);
            }
        }
    }
}

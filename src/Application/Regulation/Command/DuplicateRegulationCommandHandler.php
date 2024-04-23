<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
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
                    $cmd->isPermanent = $originalRegulationOrder->isPermanent();

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

                    if ($numberedRoad = $location->getNumberedRoad()) {
                        $cmd->numberedRoad = new SaveNumberedRoadCommand();
                        $cmd->numberedRoad->geometry = $location->getGeometry();
                        $cmd->numberedRoad->roadType = $location->getRoadType();
                        $cmd->numberedRoad->administrator = $numberedRoad->getAdministrator();
                        $cmd->numberedRoad->roadNumber = $numberedRoad->getRoadNumber();
                        $cmd->numberedRoad->fromPointNumber = $numberedRoad->getFromPointNumber();
                        $cmd->numberedRoad->fromSide = $numberedRoad->getFromSide();
                        $cmd->numberedRoad->fromAbscissa = $numberedRoad->getFromAbscissa();
                        $cmd->numberedRoad->toPointNumber = $numberedRoad->getToPointNumber();
                        $cmd->numberedRoad->toAbscissa = $numberedRoad->getToAbscissa();
                        $cmd->numberedRoad->toSide = $numberedRoad->getToSide();
                    } elseif ($namedStreet = $location->getNamedStreet()) {
                        $cmd->namedStreet = new SaveNamedStreetCommand();
                        $cmd->namedStreet->geometry = $location->getGeometry();
                        $cmd->namedStreet->roadType = $location->getRoadType();
                        $cmd->namedStreet->cityLabel = $namedStreet->getCityLabel();
                        $cmd->namedStreet->cityCode = $namedStreet->getCityCode();
                        $cmd->namedStreet->roadName = $namedStreet->getRoadName();
                        $cmd->namedStreet->fromHouseNumber = $namedStreet->getFromHouseNumber();
                        $cmd->namedStreet->toHouseNumber = $namedStreet->getToHouseNumber();
                    }

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

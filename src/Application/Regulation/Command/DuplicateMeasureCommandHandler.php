<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Measure;

final class DuplicateMeasureCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateMeasureCommand $command): Measure
    {
        $measure = $command->measure;
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();
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
            $cmd->organization = $originalRegulationOrderRecord->getOrganization();
            $cmd->roadType = $location->getRoadType();

            if ($numberedRoad = $location->getNumberedRoad()) {
                $numberedRoadCmd = new SaveNumberedRoadCommand();
                $numberedRoadCmd->geometry = $location->getGeometry();
                $numberedRoadCmd->roadType = $location->getRoadType();
                $numberedRoadCmd->administrator = $numberedRoad->getAdministrator();
                $numberedRoadCmd->roadNumber = $numberedRoad->getRoadNumber();
                $numberedRoadCmd->fromPointNumber = $numberedRoad->getFromPointNumber();
                $numberedRoadCmd->fromDepartmentCode = $numberedRoad->getFromDepartmentCode();
                $numberedRoadCmd->fromSide = $numberedRoad->getFromSide();
                $numberedRoadCmd->fromAbscissa = $numberedRoad->getFromAbscissa();
                $numberedRoadCmd->toPointNumber = $numberedRoad->getToPointNumber();
                $numberedRoadCmd->toDepartmentCode = $numberedRoad->getToDepartmentCode();
                $numberedRoadCmd->toAbscissa = $numberedRoad->getToAbscissa();
                $numberedRoadCmd->toSide = $numberedRoad->getToSide();
                $numberedRoadCmd->direction = $numberedRoad->getDirection();
                $numberedRoadCmd->storageArea = $location->getStorageArea();
                $numberedRoadCmd->prepareReferencePoints();
                $cmd->assignNumberedRoad($numberedRoadCmd);
            } elseif ($namedStreet = $location->getNamedStreet()) {
                $cmd->namedStreet = new SaveNamedStreetCommand();
                $cmd->namedStreet->geometry = $location->getGeometry();
                $cmd->namedStreet->roadType = $location->getRoadType();
                $cmd->namedStreet->cityLabel = $namedStreet->getCityLabel();
                $cmd->namedStreet->direction = $namedStreet->getDirection();
                $cmd->namedStreet->cityCode = $namedStreet->getCityCode();
                $cmd->namedStreet->roadName = $namedStreet->getRoadName();
                $cmd->namedStreet->fromHouseNumber = $namedStreet->getFromHouseNumber();
                $cmd->namedStreet->fromRoadName = $namedStreet->getFromRoadName();
                $cmd->namedStreet->toHouseNumber = $namedStreet->getToHouseNumber();
                $cmd->namedStreet->toRoadName = $namedStreet->getToRoadName();
            } elseif ($rawGeoJSON = $location->getRawGeoJSON()) {
                $cmd->rawGeoJSON = new SaveRawGeoJSONCommand();
                $cmd->rawGeoJSON->roadType = $location->getRoadType();
                $cmd->rawGeoJSON->label = $rawGeoJSON->getLabel();
                $cmd->rawGeoJSON->geometry = $location->getGeometry();
            }

            $locationCommands[] = $cmd;
        }

        $vehicleSetCommand = $measure->getVehicleSet()
            ? (new SaveVehicleSetCommand())->initFromEntity($measure->getVehicleSet())
            : null;

        $measureCommand = new SaveMeasureCommand($originalRegulationOrder);
        $measureCommand->type = $measure->getType();
        $measureCommand->createdAt = $measure->getCreatedAt();
        $measureCommand->maxSpeed = $measure->getMaxSpeed();
        $measureCommand->vehicleSet = $vehicleSetCommand;
        $measureCommand->periods = $periodCommands;
        $measureCommand->locations = $locationCommands;

        return $this->commandBus->handle($measureCommand);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
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
                $cmd->namedStreet->roadBanId = $namedStreet->getRoadBanId();
                $cmd->namedStreet->fromPointType = $namedStreet->getFromPointType();
                $cmd->namedStreet->fromHouseNumber = $namedStreet->getFromHouseNumber();
                $cmd->namedStreet->fromRoadBanId = $namedStreet->getFromRoadBanId();
                $cmd->namedStreet->fromRoadName = $namedStreet->getFromRoadName();
                $cmd->namedStreet->toPointType = $namedStreet->getToPointType();
                $cmd->namedStreet->toHouseNumber = $namedStreet->getToHouseNumber();
                $cmd->namedStreet->toRoadBanId = $namedStreet->getToRoadBanId();
                $cmd->namedStreet->toRoadName = $namedStreet->getToRoadName();
            } elseif ($rawGeoJSON = $location->getRawGeoJSON()) {
                $cmd->rawGeoJSON = new SaveRawGeoJSONCommand();
                $cmd->rawGeoJSON->roadType = $location->getRoadType();
                $cmd->rawGeoJSON->label = $rawGeoJSON->getLabel();
                // Tracé dessiné, dont les exceptions sont soustraites à l'enregistrement. Repli sur
                // la géométrie de la localisation pour les tracés antérieurs aux exceptions.
                $cmd->rawGeoJSON->geometry = $rawGeoJSON->getGeometry() ?? $location->getGeometry();
                $cmd->rawGeoJSON->exceptions = $this->duplicateExceptions($location);
            } elseif ($zone = $location->getZone()) {
                $cmd->zone = new SaveZoneCommand();
                $cmd->zone->roadType = $location->getRoadType();
                $cmd->zone->label = $zone->getLabel();
                $cmd->zone->geometry = $zone->getGeometry();
                // Tronçons déjà calculés pour ce périmètre : évite une nouvelle recherche dans la BD TOPO.
                $cmd->zone->sectionsGeometry = $location->getGeometry();
                $cmd->zone->exceptions = $this->duplicateExceptions($location);
            } elseif ($location->getRoadType() === RoadTypeEnum::WHOLE_CITY->value) {
                // « Ville entière » n'a pas de sous-entité dédiée : ses données vivent sur la localisation.
                $cmd->wholeCity = new SaveWholeCityCommand();
                $cmd->wholeCity->roadType = $location->getRoadType();
                $cmd->wholeCity->cityCode = $location->getCityCode();
                $cmd->wholeCity->cityLabel = $location->getCityLabel();
                // Géométrie déjà calculée : évite de recalculer celle de toute la ville.
                $cmd->wholeCity->geometry = $location->getGeometry();
                $cmd->wholeCity->exceptions = $this->duplicateExceptions($location);
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

    /**
     * Recopie les exceptions (voies ou tracés exclus) d'une localisation « Ville entière »,
     * « Tracé de zone » ou « Tracé libre ».
     *
     * @return SaveWholeCityExceptionCommand[]
     */
    private function duplicateExceptions(Location $location): array
    {
        $commands = [];

        foreach ($location->getExceptions() as $exception) {
            // La commande se réhydrate depuis les données structurées de l'exception d'origine.
            // Les exceptions sont toujours recréées à l'enregistrement : la référence à l'entité
            // d'origine ne sert pas à une mise à jour.
            $exceptionCommand = new SaveWholeCityExceptionCommand($exception);

            // On reprend la géométrie déjà calculée pour éviter un nouveau géocodage.
            if ($exceptionCommand->namedStreet) {
                $exceptionCommand->namedStreet->geometry = $exception->getGeometry();
            }

            $commands[] = $exceptionCommand;
        }

        return $commands;
    }
}

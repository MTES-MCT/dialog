<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\DeleteLocationCommand;
use App\Application\Regulation\Command\Period\DeletePeriodCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class SaveMeasureCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private MeasureRepositoryInterface $measureRepository,
        private CommandBusInterface $commandBus,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveMeasureCommand $command): Measure
    {
        if ($command->type != MeasureTypeEnum::SPEED_LIMITATION->value) {
            $command->maxSpeed = null;
        }

        if ($command->measure) {
            $command->measure->update($command->type, $command->maxSpeed);

            if ($command->vehicleSet) {
                $command->vehicleSet->measure = $command->measure;
                $this->commandBus->handle($command->vehicleSet);
            } else {
                $command->measure->setVehicleSet(null);
            }

            $periodsStillPresentUuids = [];

            // Periods provided with the command get created or updated...
            foreach ($command->periods as $periodCommand) {
                if ($periodCommand->period) {
                    $periodsStillPresentUuids[] = $periodCommand->period->getUuid();
                }

                $periodCommand->measure = $command->measure;
                $period = $this->commandBus->handle($periodCommand);
            }

            // Periods that were not present in the command get deleted.
            foreach ($command->measure->getPeriods() as $period) {
                if (!\in_array($period->getUuid(), $periodsStillPresentUuids)) {
                    $this->commandBus->handle(new DeletePeriodCommand($period));
                    $command->measure->removePeriod($period);
                }
            }

            $locationsStillPresentUuids = [];

            foreach ($command->locations as $index => $locationCommand) {
                if ($locationCommand->location) {
                    $locationsStillPresentUuids[] = $locationCommand->location->getUuid();
                }

                try {
                    $locationCommand->measure = $command->measure;
                    $location = $this->commandBus->handle($locationCommand);
                    $locationsStillPresentUuids[] = $location->getUuid();
                } catch (GeocodingFailureException|RoadGeocodingFailureException $e) {
                    $e->setLocationIndex($index);
                    throw $e;
                }
            }

            // Locations that weren't present in the command get deleted.
            foreach ($command->measure->getLocations() as $location) {
                if (!\in_array($location->getUuid(), $locationsStillPresentUuids)) {
                    $this->commandBus->handle(new DeleteLocationCommand($location));
                    $command->measure->removeLocation($location);
                }
            }

            return $command->measure;
        }

        $measure = $this->measureRepository->add(
            new Measure(
                uuid: $this->idFactory->make(),
                regulationOrder: $command->regulationOrder,
                type: $command->type,
                createdAt: $command->createdAt ?? $this->dateUtils->getNow(),
                maxSpeed: $command->maxSpeed,
            ),
        );

        if ($command->vehicleSet) {
            $command->vehicleSet->measure = $measure;
            $vehicleSet = $this->commandBus->handle($command->vehicleSet);
            $measure->setVehicleSet($vehicleSet);
        }

        foreach ($command->periods as $periodCommand) {
            $periodCommand->measure = $measure;
            $period = $this->commandBus->handle($periodCommand);
            $measure->addPeriod($period);
        }

        foreach ($command->locations as $index => $locationCommand) {
            $locationCommand->measure = $measure;

            try {
                $location = $this->commandBus->handle($locationCommand);
                $measure->addLocation($location);
            } catch (GeocodingFailureException|RoadGeocodingFailureException $e) {
                $e->setLocationIndex($index);
                throw $e;
            }
        }

        return $measure;
    }
}

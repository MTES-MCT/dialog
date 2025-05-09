<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;

final class SaveNumberedRoadCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private NumberedRoadRepositoryInterface $numberedRoadRepository,
    ) {
    }

    public function __invoke(SaveNumberedRoadCommand $command): NumberedRoad
    {
        if (!$command->numberedRoad instanceof NumberedRoad) {
            $numberedRoad = $this->numberedRoadRepository->add(
                new NumberedRoad(
                    uuid: $this->idFactory->make(),
                    location: $command->location,
                    direction: $command->direction,
                    roadNumber: $command->roadNumber,
                    administrator: $command->administrator,
                    fromDepartmentCode: $command->fromDepartmentCode,
                    fromPointNumber: $command->fromPointNumber,
                    fromAbscissa: $command->fromAbscissa,
                    fromSide: $command->fromSide,
                    toDepartmentCode: $command->toDepartmentCode,
                    toPointNumber: $command->toPointNumber,
                    toAbscissa: $command->toAbscissa,
                    toSide: $command->toSide,
                ),
            );

            $command->location->setStorageArea($command->storageArea);
            $command->location->setNumberedRoad($numberedRoad);

            return $numberedRoad;
        }

        $command->location->setStorageArea($command->storageArea);

        $command->numberedRoad->update(
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            fromDepartmentCode: $command->fromDepartmentCode,
            fromPointNumber: $command->fromPointNumber,
            fromAbscissa: $command->fromAbscissa,
            fromSide: $command->fromSide,
            toDepartmentCode: $command->toDepartmentCode,
            toPointNumber: $command->toPointNumber,
            toAbscissa: $command->toAbscissa,
            toSide: $command->toSide,
            direction: $command->direction,
        );

        return $command->numberedRoad;
    }
}

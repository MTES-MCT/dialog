<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Location\Location;

final class SaveLocationCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SaveLocationCommand $command): Location
    {
        $command->clean();

        if ($location = $command->location) {
            if ($location->getNamedStreet() && $command->namedStreet === null) {
                // Delete named street when road type changed
                $this->commandBus->handle(new DeleteNamedStreetCommand($location->getNamedStreet()));
            } elseif ($location->getNumberedRoad() && $command->numberedRoad === null) {
                // Delete numbered road when road type changed
                $this->commandBus->handle(new DeleteNumberedRoadCommand($location->getNumberedRoad()));
            }
        }

        if ($command->namedStreet) {
            $command->namedStreet->measure = $command->measure;
            $command->namedStreet->roadType = $command->roadType;

            return $this->commandBus->handle($command->namedStreet);
        } elseif ($command->numberedRoad) {
            $command->numberedRoad->measure = $command->measure;
            $command->numberedRoad->roadType = $command->roadType;

            return $this->commandBus->handle($command->numberedRoad);
        }

        throw new \LogicException('Location road type not managed');
    }
}

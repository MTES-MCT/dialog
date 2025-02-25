<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommand;
use App\Application\Regulation\Command\Location\GeocodeLocationsWithoutGeometryCommandResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:location:geometry:sync',
    description: 'Geocode locations without a geometry',
    hidden: false,
)]
class SyncLocationGeometriesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var GeocodeLocationsWithoutGeometryCommandResult */
        $result = $this->commandBus->handle(new GeocodeLocationsWithoutGeometryCommand());

        $isSuccess = empty($result->exceptions);

        $output->writeln(json_encode([
            'level' => $isSuccess ? 'INFO' : 'ERROR',
            'message' => $isSuccess ? 'success' : 'some locations failed to be geocoded',
            'num_locations' => $result->numLocations,
            'num_updated' => \count($result->updatedLocationUuids),
        ]));

        foreach ($result->updatedLocationUuids as $uuid) {
            $output->writeln(json_encode([
                'level' => 'DEBUG',
                'message' => 'updated',
                'location_uuid' => $uuid,
            ]));
        }

        foreach ($result->exceptions as $locationUuid => $excItem) {
            $output->writeln(json_encode([
                'level' => 'ERROR',
                'message' => 'geocoding failed',
                'location_uuid' => $locationUuid,
                'exc' => $excItem->getMessage(),
            ]));
        }

        return $isSuccess ? Command::SUCCESS : Command::FAILURE;
    }
}

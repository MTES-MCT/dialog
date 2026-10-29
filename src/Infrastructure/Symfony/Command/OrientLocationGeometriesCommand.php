<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommand;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommandResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:location:geometry:orient',
    description: 'Recalcule la géométrie des emprises à sens unique pour l\'orienter du point A vers le point B',
    hidden: false,
)]
class OrientLocationGeometriesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Recalcule et compare les géométries sans rien enregistrer');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        /** @var RecomputeOneWayLocationGeometriesCommandResult */
        $result = $this->commandBus->handle(new RecomputeOneWayLocationGeometriesCommand(dryRun: $dryRun));

        $isSuccess = empty($result->exceptions);

        $output->writeln(json_encode([
            'level' => $isSuccess ? 'INFO' : 'ERROR',
            'message' => $isSuccess ? 'success' : 'some locations failed to be geocoded',
            'dry_run' => $dryRun,
            'num_locations' => $result->numLocations,
            'num_updated' => \count($result->updatedLocationUuids),
            'num_unchanged' => \count($result->unchangedLocationUuids),
        ]));

        foreach ($result->updatedLocationUuids as $uuid) {
            $output->writeln(json_encode([
                'level' => 'DEBUG',
                'message' => $dryRun ? 'would update' : 'updated',
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

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\ConvertPolygonsToZonesCommand;
use App\Application\Regulation\Command\Location\ConvertPolygonsToZonesCommandResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:location:migrate_polygons_to_zones',
    description: 'Convert locations with a polygon geometry into zones (one-shot, #1873)',
    hidden: false,
)]
class MigratePolygonsToZonesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Liste les localisations qui seraient converties, sans rien modifier');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        /** @var ConvertPolygonsToZonesCommandResult */
        $result = $this->commandBus->handle(new ConvertPolygonsToZonesCommand(dryRun: $dryRun));

        $isSuccess = empty($result->exceptions);

        $output->writeln(json_encode([
            'level' => $isSuccess ? 'INFO' : 'ERROR',
            'message' => $isSuccess ? 'success' : 'some locations failed to be converted',
            'dry_run' => $dryRun,
            'num_locations' => $result->numLocations,
            'num_converted' => \count($result->convertedLocationUuids),
        ]));

        foreach ($result->convertedLocationUuids as $uuid) {
            $output->writeln(json_encode([
                'level' => 'DEBUG',
                'message' => $dryRun ? 'would convert' : 'converted',
                'location_uuid' => $uuid,
            ]));
        }

        foreach ($result->exceptions as $locationUuid => $excItem) {
            $output->writeln(json_encode([
                'level' => 'ERROR',
                'message' => 'conversion failed',
                'location_uuid' => $locationUuid,
                'exc' => $excItem->getMessage(),
            ]));
        }

        return $isSuccess ? Command::SUCCESS : Command::FAILURE;
    }
}

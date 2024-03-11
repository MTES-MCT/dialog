<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:locations:sync',
    description: 'Sync locations by running an empty update',
    hidden: false,
)]
class SyncLocationsCommand extends Command
{
    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->locationRepository->iterFindAll() as $location) {
            $output->writeln(json_encode([
                'status' => 'info',
                'message' => 'syncing',
                'cityLabel' => $location->getCityLabel(),
                'roadName' => $location->getRoadName(),
            ]));

            try {
                $this->commandBus->handle(new SaveLocationCommand($location));
            } catch (GeocodingFailureException $exc) {
                $output->writeln(json_encode([
                    'status' => 'error',
                    'message' => 'geocoding_error',
                    'error' => $exc->getMessage(),
                ]));
            }
        }

        return Command::SUCCESS;
    }
}

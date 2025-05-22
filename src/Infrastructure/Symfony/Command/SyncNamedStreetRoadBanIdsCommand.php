<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\UpdateNamedStreetsWithoutRoadBanIdsCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:named_street:road_ban_ids:sync',
    description: 'Fill roadBanId, fromRoadBanId and toRoadBanId fields in NamedStreet',
    hidden: false,
)]
class SyncNamedStreetRoadBanIdsCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $numUpdated = 0;
        $numErrors = 0;

        $command = new UpdateNamedStreetsWithoutRoadBanIdsCommand(function (array $event) use ($output, &$numUpdated, &$numErrors): void {
            if ($event['message'] === 'updated') {
                ++$numUpdated;
            }

            if ($event['level'] === 'ERROR') {
                ++$numErrors;
            }

            $output->writeln(json_encode($event));
        });

        $numNamedStreets = $this->commandBus->handle($command);

        $isSuccess = $numErrors === 0;

        $output->writeln(json_encode([
            'level' => $isSuccess ? 'INFO' : 'ERROR',
            'message' => $isSuccess ? 'success' : 'some road BAN IDs failed to be found',
            'num_total' => $numNamedStreets,
            'num_updated' => $numUpdated,
            'num_errors' => $numErrors,
        ]));

        return $isSuccess ? Command::SUCCESS : Command::FAILURE;
    }
}

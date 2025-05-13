<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\Location\UpdateNamedStreetsWithoutRoadBanIdsCommand;
use App\Application\Regulation\Command\Location\UpdateNamedStreetsWithoutRoadBanIdsCommandResult;
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
        /** @var UpdateNamedStreetsWithoutRoadBanIdsCommandResult */
        $result = $this->commandBus->handle(new UpdateNamedStreetsWithoutRoadBanIdsCommand());

        $isSuccess = empty($result->exceptions);

        $output->writeln(json_encode([
            'level' => $isSuccess ? 'INFO' : 'ERROR',
            'message' => $isSuccess ? 'success' : 'some road BAN IDs failed to be found',
            'num_candidates' => $result->numNamedStreets,
            'num_updated' => \count($result->updatedUuids),
            'num_errors' => \count($result->exceptions),
        ]));

        foreach ($result->updatedUuids as $uuid) {
            $output->writeln(json_encode([
                'level' => 'DEBUG',
                'message' => 'updated',
                'uuid' => $uuid,
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

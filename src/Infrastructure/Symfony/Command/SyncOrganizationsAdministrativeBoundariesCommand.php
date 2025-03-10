<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\SyncOrganizationsAdministrativeBoundariesCommand as CommandSyncOrganizationsAdministrativeBoundariesCommand;
use App\Application\Organization\Command\SyncOrganizationsAdministrativeBoundariesCommandResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:organizations:administrative-boundaries:sync',
    description: 'Sync organizations administratives boundaries',
    hidden: false,
)]
class SyncOrganizationsAdministrativeBoundariesCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SyncOrganizationsAdministrativeBoundariesCommandResult */
        $result = $this->commandBus->handle(new CommandSyncOrganizationsAdministrativeBoundariesCommand());

        $output->writeln(\sprintf('<info>%s</info>', $result->totalOrganizations . ' organization(s) found'));
        $output->writeln(\sprintf('<info>%s</info>', $result->updatedOrganizations . ' organization(s) updated'));

        return Command::SUCCESS;
    }
}

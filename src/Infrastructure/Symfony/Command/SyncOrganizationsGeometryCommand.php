<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Organization\Command\SyncOrganizationAdministrativeBoundariesCommand;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:organizations:geometry:sync',
    description: 'Sync organizations geometries',
    hidden: false,
)]
class SyncOrganizationsGeometryCommand extends Command
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizations = $this->organizationRepository->findAllWithCodes();

        foreach ($organizations as $organization) {
            $this->commandBus->dispatchAsync(new SyncOrganizationAdministrativeBoundariesCommand($organization->getUuid()));
        }

        $output->writeln(\sprintf('<info>%s</info>', \count($organizations) . ' organizations in process'));

        return Command::SUCCESS;
    }
}

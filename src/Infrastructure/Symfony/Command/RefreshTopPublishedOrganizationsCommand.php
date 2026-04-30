<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:map:refresh-top-published-organizations',
    description: 'Refreshes the cache table top_published_organization (top 10 organizations by number of published regulation orders, with their bbox). Used to pick a default initial map view for anonymous users.',
    hidden: false,
)]
final class RefreshTopPublishedOrganizationsCommand extends Command
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->organizationRepository->refreshTopPublishedOrganizations();

        $output->writeln('<info>top_published_organization refreshed.</info>');

        return Command::SUCCESS;
    }
}

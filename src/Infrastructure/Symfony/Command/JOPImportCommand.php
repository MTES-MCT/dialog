<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\Integration\JOP\JOPExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:jop:import',
    description: 'Import JOP data',
    hidden: false,
)]
class JOPImportCommand extends Command
{
    public function __construct(
        private JOPExecutor $executor,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->executor->execute();
        } catch (\RuntimeException $exc) {
            $output->writeln($exc->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

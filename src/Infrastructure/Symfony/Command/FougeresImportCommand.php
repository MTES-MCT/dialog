<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\Fougeres\FougeresExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fougeres:import',
    description: 'Import Litteralis data of Fougères',
    hidden: false,
)]
class FougeresImportCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
        private FougeresExecutor $executor,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reporter = new Reporter($this->logger);
        $now = $this->dateUtils->getNow();

        try {
            $report = $this->executor->execute($now, $reporter);

            $output->write($report);
        } catch (\RuntimeException $exc) {
            $output->writeln($exc->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

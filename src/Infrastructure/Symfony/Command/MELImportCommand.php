<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\MEL\MELExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mel:import',
    description: 'Import Litteralis data of Métropole Européenne de Lille (MEL)',
    hidden: false,
)]
class MELImportCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
        private MELExecutor $executor,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reporter = new Reporter($this->logger);
        $now = $this->dateUtils->getNow();

        try {
            $report = $this->executor->execute(laterThan: $now, reporter: $reporter);

            $output->write($report);
        } catch (\RuntimeException $exc) {
            $output->writeln($exc->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

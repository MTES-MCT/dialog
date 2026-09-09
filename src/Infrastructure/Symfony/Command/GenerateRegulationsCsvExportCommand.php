<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Regulation\RegulationExportCsvGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:regulations:generate-csv-export',
    description: 'Pre-generates the complete CSV export of the national restrictions database (served from cache for fast download). Meant to run nightly.',
    hidden: false,
)]
final class GenerateRegulationsCsvExportCommand extends Command
{
    public function __construct(
        private readonly RegulationExportCsvGeneratorInterface $csvGenerator,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->csvGenerator->generate();

        $output->writeln('<info>Regulations CSV export generated.</info>');

        return Command::SUCCESS;
    }
}

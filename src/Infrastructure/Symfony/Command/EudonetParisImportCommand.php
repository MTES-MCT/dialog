<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Infrastructure\EudonetParis\EudonetParisExecutor;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:eudonet_paris:import',
    description: 'Import data from the Eudonet Paris API',
    hidden: false,
)]
class EudonetParisImportCommand extends Command
{
    public function __construct(
        private EudonetParisExecutor $eudonetParisExecutor,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $nowUTC = $this->dateUtils->getNow();

        try {
            $this->eudonetParisExecutor->execute(laterThanUTC: $nowUTC);
        } catch (EudonetParisException) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

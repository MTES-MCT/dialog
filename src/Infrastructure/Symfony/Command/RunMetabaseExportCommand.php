<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Domain\Statistics\Repository\StatisticsRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:metabase:export',
    description: 'Export statistics to Metabase',
    hidden: false,
)]
class RunMetabaseExportCommand extends Command
{
    public function __construct(
        private DateUtilsInterface $dateUtils,
        private StatisticsRepositoryInterface $statisticsRepository,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->dateUtils->getNow();

        $this->statisticsRepository->addUserActiveStatistics($now);

        return Command::SUCCESS;
    }
}

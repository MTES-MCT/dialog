<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\BacIdf\BacIdfExecutor;
use App\Infrastructure\BacIdf\Exception\BacIdfException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:bac_idf:import',
    description: 'Import data from a BAC-IDF extract',
    hidden: false,
)]
class BacIdfImportCommand extends Command
{
    public function __construct(
        private BacIdfExecutor $executor,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->executor->execute();
        } catch (BacIdfException) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

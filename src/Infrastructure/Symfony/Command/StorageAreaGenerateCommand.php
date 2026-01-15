<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\Data\StorageArea\StorageAreaMigrationGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

#[AsCommand(
    name: 'app:storage_area:generate',
    description: 'Generate migration lines for data/aires_de_stockage.csv',
    hidden: false,
)]
class StorageAreaGenerateCommand extends Command
{
    public function __construct(
        private readonly DecoderInterface $decoder,
        private readonly StorageAreaMigrationGenerator $storageAreaMigrationGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        $csv = file_get_contents($path);

        $rows = $this->decoder->decode($csv, 'csv');
        $sql = $this->storageAreaMigrationGenerator->makeMigrationSql($rows);

        $output->writeln(\sprintf('$this->addSql(\'%s\');', str_replace("'", "\\'", $sql)));

        return Command::SUCCESS;
    }
}

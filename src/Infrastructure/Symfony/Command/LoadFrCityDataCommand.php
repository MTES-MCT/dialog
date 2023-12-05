<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:data:fr_city',
    description: 'Load data into the fr_city table in the configured environment',
    hidden: false,
)]
class LoadFrCityDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = file_get_contents($this->projectDir . '/data/fr_city.sql');

        $statements = explode(';\n', $sql);

        foreach ($statements as $statement) {
            $this->em->getConnection()->executeStatement(sprintf('%s;', $statement));
        }

        return Command::SUCCESS;
    }
}

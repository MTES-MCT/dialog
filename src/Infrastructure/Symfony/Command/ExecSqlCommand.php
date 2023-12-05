<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:exec_sql',
    description: 'Execute statements of an SQL file in the database of the configured environment',
    hidden: false,
)]
class ExecSqlCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'The input file path as a string');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $sql = file_get_contents($path);

        $statements = explode(';\n', $sql);

        foreach ($statements as $statement) {
            $this->em->getConnection()->executeStatement("$statement;");
        }

        return Command::SUCCESS;
    }
}

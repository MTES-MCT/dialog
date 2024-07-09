<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Infrastructure\Litteralis\LitteralisClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mel:regulation-get',
    description: 'Get "emprises" of regulation by identifier (arretesrcid) in MEL Litteralis data',
    hidden: false,
)]
class MELRegulationGet extends Command
{
    public function __construct(
        private LitteralisClient $client,
        string $melCredentials,
    ) {
        parent::__construct();

        $client->setCredentials($melCredentials);
    }

    public function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        $features = $this->client->fetchAllByRegulationId($id);

        $output->writeln(json_encode($features, JSON_UNESCAPED_UNICODE & JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisExecutor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:litteralis:import',
    description: 'Import Litteralis data of registered organizations',
    hidden: false,
)]
class LitteralisImportCommand extends Command
{
    public function __construct(
        LoggerInterface $logger,
        private array $litteralisEnabledOrgs,
        private LitteralisCredentials $litteralisCredentials,
        private Reporter $reporter,
        private LitteralisExecutor $executor,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct();

        $this->reporter->setLogger($logger);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = $this->dateUtils->getNow();

        $returnCode = Command::SUCCESS;

        foreach ($this->litteralisEnabledOrgs as $name) {
            $this->reporter->reset();

            $orgId = $this->litteralisCredentials->getOrgId($name);

            try {
                $report = $this->executor->execute($name, $orgId, $now, $this->reporter);

                $output->write($report);
            } catch (\Exception $exc) {
                $output->writeln($exc->getMessage());

                $returnCode = Command::FAILURE;
            }
        }

        return $returnCode;
    }
}

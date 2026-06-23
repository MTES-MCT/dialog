<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regulations:delete-from-csv',
    description: 'Delete a list of regulation orders from a CSV file (columns: identifier, organization)',
    hidden: false,
)]
class DeleteRegulationsFromCsvCommand extends Command
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private readonly CommandBusInterface $commandBus,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file (columns: identifier, organization)')
            ->addOption('delimiter', null, InputOption::VALUE_REQUIRED, 'CSV delimiter', ',')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate the deletion without modifying the database');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');
        $delimiter = $input->getOption('delimiter');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!is_file($file) || !is_readable($file)) {
            $io->error(\sprintf('File not found or not readable: %s', $file));

            return Command::FAILURE;
        }

        $handle = fopen($file, 'r');

        if ($handle === false) {
            $io->error(\sprintf('Unable to open file: %s', $file));

            return Command::FAILURE;
        }

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');

        if ($header === false) {
            $io->error('The CSV file is empty.');
            fclose($handle);

            return Command::FAILURE;
        }

        $header = array_map(static fn ($value): string => strtolower(trim((string) $value)), $header);
        $identifierIndex = array_search('identifier', $header, true);
        $organizationIndex = array_search('organization', $header, true);

        if ($identifierIndex === false || $organizationIndex === false) {
            $io->error('The CSV file must contain the columns "identifier" and "organization".');
            fclose($handle);

            return Command::FAILURE;
        }

        $deleted = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            ++$line;

            if ($row === [null] || $row === []) {
                continue;
            }

            $identifier = isset($row[$identifierIndex]) ? trim((string) $row[$identifierIndex]) : '';
            $organizationName = isset($row[$organizationIndex]) ? trim((string) $row[$organizationIndex]) : '';

            if ($identifier === '' || $organizationName === '') {
                $errors[] = \sprintf('Line %d: missing identifier or organization.', $line);
                continue;
            }

            $organization = $this->organizationRepository->findOneByName($organizationName);

            if ($organization === null) {
                $errors[] = \sprintf('Line %d: organization "%s" not found.', $line, $organizationName);
                continue;
            }

            $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneByIdentifierInOrganization($identifier, $organization);

            if ($regulationOrderRecord === null) {
                $errors[] = \sprintf('Line %d: regulation order "%s" not found in organization "%s".', $line, $identifier, $organizationName);
                continue;
            }

            if ($dryRun) {
                $io->writeln(\sprintf('<comment>[dry-run]</comment> Would delete "%s" (%s).', $identifier, $organizationName));
                ++$deleted;
                continue;
            }

            try {
                $this->commandBus->handle(new DeleteRegulationCommand([$organization->getUuid()], $regulationOrderRecord));
                $this->entityManager->flush();
                $io->writeln(\sprintf('<info>Deleted "%s" (%s).</info>', $identifier, $organizationName));
                ++$deleted;
            } catch (\Throwable $exc) {
                $errors[] = \sprintf('Line %d: failed to delete "%s" (%s): %s', $line, $identifier, $organizationName, $exc->getMessage());
            }
        }

        fclose($handle);

        $io->newLine();

        if ($dryRun) {
            $io->writeln(\sprintf('<info>%d regulation order(s) would be deleted.</info>', $deleted));
        } else {
            $io->writeln(\sprintf('<info>%d regulation order(s) deleted.</info>', $deleted));
        }

        if ($errors !== []) {
            $io->newLine();
            $io->writeln(\sprintf('<error>%d error(s):</error>', \count($errors)));

            foreach ($errors as $error) {
                $io->writeln(\sprintf(' - %s', $error));
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

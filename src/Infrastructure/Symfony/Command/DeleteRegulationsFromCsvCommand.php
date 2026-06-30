<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Command\GenerateDatexCommand;
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
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

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
        private readonly CsvEncoder $csvEncoder = new CsvEncoder(),
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

        $content = file_get_contents($file);

        if ($content === false) {
            $io->error(\sprintf('Unable to read file: %s', $file));

            return Command::FAILURE;
        }

        try {
            $rows = $this->csvEncoder->decode($content, CsvEncoder::FORMAT, [
                CsvEncoder::DELIMITER_KEY => $delimiter,
                CsvEncoder::AS_COLLECTION_KEY => true,
            ]);
        } catch (UnexpectedValueException $exc) {
            $io->error(\sprintf('Unable to parse CSV file: %s', $exc->getMessage()));

            return Command::FAILURE;
        }

        if ($rows === []) {
            $io->error('The CSV file is empty.');

            return Command::FAILURE;
        }

        $columns = array_keys($rows[0]);

        if (!\in_array('identifier', $columns, true) || !\in_array('organization', $columns, true)) {
            $io->error('The CSV file must contain the columns "identifier" and "organization".');

            return Command::FAILURE;
        }

        $deleted = 0;
        $errors = [];
        $line = 1;

        foreach ($rows as $row) {
            ++$line;

            $identifier = trim((string) ($row['identifier'] ?? ''));
            $organizationUuid = trim((string) ($row['organization'] ?? ''));

            if ($identifier === '' || $organizationUuid === '') {
                $errors[] = \sprintf('Line %d: missing identifier or organization.', $line);
                continue;
            }

            $organization = $this->organizationRepository->findOneByUuid($organizationUuid);

            if ($organization === null) {
                $errors[] = \sprintf('Line %d: organization "%s" not found.', $line, $organizationUuid);
                continue;
            }

            $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneByIdentifierInOrganization($identifier, $organization);

            if ($regulationOrderRecord === null) {
                $errors[] = \sprintf('Line %d: regulation order "%s" not found in organization "%s" (%s).', $line, $identifier, $organization->getName(), $organizationUuid);
                continue;
            }

            if ($dryRun) {
                $io->writeln(\sprintf('<comment>[dry-run]</comment> Would delete "%s" (%s).', $identifier, $organization->getName()));
                ++$deleted;
                continue;
            }

            try {
                $this->commandBus->handle(new DeleteRegulationCommand([$organization->getUuid()], $regulationOrderRecord));
                $this->entityManager->flush();
                $io->writeln(\sprintf('<info>Deleted "%s" (%s).</info>', $identifier, $organization->getName()));
                ++$deleted;
            } catch (\Throwable $exc) {
                $errors[] = \sprintf('Line %d: failed to delete "%s" (%s): %s', $line, $identifier, $organization->getName(), $exc->getMessage());
            }
        }

        $io->newLine();

        if ($dryRun) {
            $io->writeln(\sprintf('<info>%d regulation order(s) would be deleted.</info>', $deleted));
        } else {
            $io->writeln(\sprintf('<info>%d regulation order(s) deleted.</info>', $deleted));

            if ($deleted > 0) {
                $this->commandBus->dispatchAsync(new GenerateDatexCommand());
            }
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

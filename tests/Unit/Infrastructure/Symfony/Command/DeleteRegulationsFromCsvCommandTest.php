<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Symfony\Command\DeleteRegulationsFromCsvCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DeleteRegulationsFromCsvCommandTest extends TestCase
{
    private OrganizationRepositoryInterface $organizationRepository;
    private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository;
    private CommandBusInterface $commandBus;
    private EntityManagerInterface $entityManager;
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function makeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dialog_csv_');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;

        return $path;
    }

    private function createCommandTester(): CommandTester
    {
        $command = new DeleteRegulationsFromCsvCommand(
            $this->organizationRepository,
            $this->regulationOrderRecordRepository,
            $this->commandBus,
            $this->entityManager,
        );

        return new CommandTester($command);
    }

    public function testName(): void
    {
        $command = new DeleteRegulationsFromCsvCommand(
            $this->organizationRepository,
            $this->regulationOrderRecordRepository,
            $this->commandBus,
            $this->entityManager,
        );

        $this->assertSame('app:regulations:delete-from-csv', $command->getName());
    }

    public function testExecute(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('org-uuid');
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('org-uuid')
            ->willReturn($organization);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByIdentifierInOrganization')
            ->with('FO1/2023', $organization)
            ->willReturn($regulationOrderRecord);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(function (DeleteRegulationCommand $command) use ($regulationOrderRecord): bool {
                return $command->userOrganizationUuids === ['org-uuid']
                    && $command->regulationOrderRecord === $regulationOrderRecord;
            }));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->commandBus
            ->expects(self::once())
            ->method('dispatchAsync')
            ->with(self::isInstanceOf(\App\Application\Regulation\Command\GenerateDatexCommand::class));
        $file = $this->makeCsv("identifier,organization\nFO1/2023,org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('1 regulation order(s) deleted.', $commandTester->getDisplay());
    }

    public function testExecuteWithCustomDelimiter(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('org-uuid');
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->organizationRepository
            ->method('findOneByUuid')
            ->willReturn($organization);
        $this->regulationOrderRecordRepository
            ->method('findOneByIdentifierInOrganization')
            ->willReturn($regulationOrderRecord);

        $this->commandBus->expects(self::once())->method('handle');
        $this->entityManager->expects(self::once())->method('flush');

        $file = $this->makeCsv("identifier;organization\nFO1/2023;org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file, '--delimiter' => ';']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('1 regulation order(s) deleted.', $commandTester->getDisplay());
    }

    public function testDryRunDoesNotDelete(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->organizationRepository
            ->method('findOneByUuid')
            ->willReturn($organization);
        $this->regulationOrderRecordRepository
            ->method('findOneByIdentifierInOrganization')
            ->willReturn($regulationOrderRecord);

        $this->commandBus->expects(self::never())->method('handle');
        $this->commandBus->expects(self::never())->method('dispatchAsync');
        $this->entityManager->expects(self::never())->method('flush');

        $file = $this->makeCsv("identifier,organization\nFO1/2023,org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file, '--dry-run' => true]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('[dry-run]', $commandTester->getDisplay());
        $this->assertStringContainsString('1 regulation order(s) would be deleted.', $commandTester->getDisplay());
    }

    public function testOrganizationNotFound(): void
    {
        $this->organizationRepository
            ->method('findOneByUuid')
            ->willReturn(null);

        $this->commandBus->expects(self::never())->method('handle');

        $file = $this->makeCsv("identifier,organization\nFO1/2023,unknown-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('organization "unknown-uuid" not found', $commandTester->getDisplay());
        $this->assertStringContainsString('0 regulation order(s) deleted.', $commandTester->getDisplay());
    }

    public function testRegulationOrderNotFound(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->organizationRepository
            ->method('findOneByUuid')
            ->willReturn($organization);
        $this->regulationOrderRecordRepository
            ->method('findOneByIdentifierInOrganization')
            ->willReturn(null);

        $this->commandBus->expects(self::never())->method('handle');

        $file = $this->makeCsv("identifier,organization\nUNKNOWN/2023,org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('regulation order "UNKNOWN/2023" not found', $commandTester->getDisplay());
    }

    public function testMissingValuesInRow(): void
    {
        $this->organizationRepository->expects(self::never())->method('findOneByUuid');

        $file = $this->makeCsv("identifier,organization\n,org-uuid\nFO1/2023,\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('missing identifier or organization', $commandTester->getDisplay());
    }

    public function testContinuesAfterDeleteException(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('org-uuid');
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $this->organizationRepository
            ->method('findOneByUuid')
            ->willReturn($organization);
        $this->regulationOrderRecordRepository
            ->method('findOneByIdentifierInOrganization')
            ->willReturn($regulationOrderRecord);

        $this->commandBus
            ->method('handle')
            ->willThrowException(new \RuntimeException('boom'));

        $file = $this->makeCsv("identifier,organization\nFO1/2023,org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('failed to delete "FO1/2023"', $commandTester->getDisplay());
        $this->assertStringContainsString('boom', $commandTester->getDisplay());
    }

    public function testMissingColumns(): void
    {
        $this->organizationRepository->expects(self::never())->method('findOneByUuid');

        $file = $this->makeCsv("foo,bar\nFO1/2023,org-uuid\n");

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('must contain the columns "identifier" and "organization"', $commandTester->getDisplay());
    }

    public function testEmptyFile(): void
    {
        $file = $this->makeCsv('');

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('empty', $commandTester->getDisplay());
    }

    public function testFileNotFound(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['file' => '/tmp/does-not-exist-dialog-unit.csv']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('File not found or not readable', $commandTester->getDisplay());
    }
}

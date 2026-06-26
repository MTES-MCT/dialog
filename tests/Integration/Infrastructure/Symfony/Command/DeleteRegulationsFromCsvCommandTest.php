<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Infrastructure\Symfony\Command\DeleteRegulationsFromCsvCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DeleteRegulationsFromCsvCommandTest extends KernelTestCase
{
    private function makeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dialog_csv_');
        file_put_contents($path, $content);

        return $path;
    }

    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $organizationRepository = $container->get(OrganizationRepositoryInterface::class);
        $regulationOrderRecordRepository = $container->get(RegulationOrderRecordRepositoryInterface::class);

        $organization = $organizationRepository->findOneByUuid(OrganizationFixture::SEINE_SAINT_DENIS_ID);
        self::assertNotNull($regulationOrderRecordRepository->findOneByIdentifierInOrganization(RegulationOrderFixture::TYPICAL_IDENTIFIER, $organization));

        $file = $this->makeCsv(\sprintf(
            "identifier,organization\n%s,%s\n",
            RegulationOrderFixture::TYPICAL_IDENTIFIER,
            OrganizationFixture::SEINE_SAINT_DENIS_ID,
        ));

        $command = $container->get(DeleteRegulationsFromCsvCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['file' => $file]);

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 regulation order(s) deleted.', $commandTester->getDisplay());
        self::assertNull($regulationOrderRecordRepository->findOneByIdentifierInOrganization(RegulationOrderFixture::TYPICAL_IDENTIFIER, $organization));

        unlink($file);
    }

    public function testDryRunDoesNotDelete(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $organizationRepository = $container->get(OrganizationRepositoryInterface::class);
        $regulationOrderRecordRepository = $container->get(RegulationOrderRecordRepositoryInterface::class);

        $file = $this->makeCsv(\sprintf(
            "identifier,organization\n%s,%s\n",
            RegulationOrderFixture::TYPICAL_IDENTIFIER,
            OrganizationFixture::SEINE_SAINT_DENIS_ID,
        ));

        $command = $container->get(DeleteRegulationsFromCsvCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['file' => $file, '--dry-run' => true]);

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('would be deleted', $commandTester->getDisplay());

        $organization = $organizationRepository->findOneByUuid(OrganizationFixture::SEINE_SAINT_DENIS_ID);
        self::assertNotNull($regulationOrderRecordRepository->findOneByIdentifierInOrganization(RegulationOrderFixture::TYPICAL_IDENTIFIER, $organization));

        unlink($file);
    }

    public function testReportsErrorsForUnknownRows(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $file = $this->makeCsv(\sprintf(
            "identifier,organization\nUNKNOWN/2023,%s\nFO1/2023,00000000-0000-0000-0000-000000000000\n",
            OrganizationFixture::SEINE_SAINT_DENIS_ID,
        ));

        $command = $container->get(DeleteRegulationsFromCsvCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['file' => $file]);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $display = $commandTester->getDisplay();
        self::assertStringContainsString('not found', $display);

        unlink($file);
    }

    public function testFailsWhenFileMissing(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $command = $container->get(DeleteRegulationsFromCsvCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['file' => '/tmp/does-not-exist-dialog.csv']);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}

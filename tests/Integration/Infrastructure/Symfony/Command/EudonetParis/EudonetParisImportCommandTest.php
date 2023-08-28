<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command\EudonetParis;

use App\Infrastructure\Symfony\Command\EudonetParisImportCommand;
use App\Tests\Mock\EudonetParis\EudonetParisMockHttpClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class EudonetParisImportCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(EudonetParisImportCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        /** @var EudonetParisMockHttpClient */
        $mockHttpClient = $container->get('eudonet_paris.http.client');
        $mockHttpClient->assertExpectedRequestsMade();

        $output = implode("\n", [
            'Processed: 1',
            'Created: 1 (100.0 %)',
            'Skipped: 0 (0.0 %)',
            'Messages:', '',
        ]);
        $this->assertSame($output, $commandTester->getDisplay());
    }
}

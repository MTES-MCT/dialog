<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\StorageAreaGenerateCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class StorageAreaGenerateCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $command = $container->get(StorageAreaGenerateCommand::class);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['path' => __DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv']);
        $commandTester->assertCommandIsSuccessful();

        $sql = $commandTester->getDisplay();

        $this->assertStringStartsWith('$this->addSql(\'INSERT INTO storage_area', $sql);
        // 4 lignes dans le CSV de test, 1 est une Autoroute (ignorée)
        $this->assertSame(3, substr_count($sql, 'uuid_generate_v4()'));
    }
}

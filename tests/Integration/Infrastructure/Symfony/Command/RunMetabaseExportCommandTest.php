<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Symfony\Command;

use App\Infrastructure\Symfony\Command\RunMetabaseExportCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RunMetabaseExportCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $command = $container->get(RunMetabaseExportCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        /** @var \Doctrine\DBAL\Connection */
        $metabaseConnection = $container->get('doctrine.dbal.metabase_connection');
        $rows = $metabaseConnection->fetchAllAssociative('SELECT * FROM analytics_user_active');
        $this->assertCount(3, $rows);
        $this->assertEquals(['id', 'uploaded_at', 'last_active_at'], array_keys($rows[0]));

        $this->assertSame('2023-06-09 00:00:00', $rows[0]['uploaded_at']);
        $this->assertSame('2024-06-07 00:00:00', $rows[0]['last_active_at'])
        ;
        $this->assertSame('2023-06-09 00:00:00', $rows[1]['uploaded_at']);
        $this->assertSame('2024-06-08 00:00:00', $rows[1]['last_active_at']);

        $this->assertSame('2023-06-09 00:00:00', $rows[2]['uploaded_at']);
        $this->assertSame(null, $rows[2]['last_active_at']);
    }
}

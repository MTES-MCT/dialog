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

        $executionDate = '2023-06-09 00:00:00'; // Defined in DateUtilsMock

        $container = static::getContainer();
        $command = $container->get(RunMetabaseExportCommand::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        /** @var \Doctrine\DBAL\Connection */
        $metabaseConnection = $container->get('doctrine.dbal.metabase_connection');

        // Check count statistics
        $rows = $metabaseConnection->fetchAllAssociative('SELECT * FROM analytics_count');
        $this->assertCount(6, $rows);
        $this->assertEquals(['id', 'uploaded_at', 'name', 'value'], array_keys($rows[0]));

        $counts = [];

        foreach ($rows as $row) {
            $this->assertSame($executionDate, $row['uploaded_at']);
            $counts[$row['name']] = $row['value'];
        }

        $this->assertEquals([
            'users' => 3,
            'organizations' => 2,
            // Only counts regulations outside of DiaLog org
            'regulationOrderRecords' => 1,
            'regulationOrderRecords.published' => 1,
            'regulationOrderRecords.permanent' => 0,
            'regulationOrderRecords.temporary' => 1,
        ], $counts);

        // Check user active statistics
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

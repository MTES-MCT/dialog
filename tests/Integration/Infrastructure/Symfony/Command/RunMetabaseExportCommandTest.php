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
        $this->assertCount(7, $rows);
        $this->assertEquals(['id', 'uploaded_at', 'name', 'value'], array_keys($rows[0]));

        $counts = [];

        foreach ($rows as $row) {
            $this->assertSame($executionDate, $row['uploaded_at']);
            $counts[$row['name']] = $row['value'];
        }

        $this->assertEquals([
            'users' => 3,
            'organizations' => 3,
            // Only counts regulations outside of DiaLog org
            'regulationOrderRecords' => 10,
            'regulationOrderRecords.published' => 4,
            'regulationOrderRecords.permanent' => 1,
            'regulationOrderRecords.temporary' => 9,
            'cifs.incidents' => 13,
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

        // Check organization extract (organizations excluding DiaLog org)
        $orgRows = $metabaseConnection->fetchAllAssociative('SELECT * FROM analytics_organization_extract');
        $this->assertGreaterThanOrEqual(1, \count($orgRows));
        $this->assertEqualsCanonicalizing(
            ['id', 'uploaded_at', 'organization_uuid', 'organization_name', 'organization_type', 'nb_users', 'nb_published_regulation_orders'],
            array_keys($orgRows[0]),
        );
        foreach ($orgRows as $row) {
            $this->assertSame($executionDate, $row['uploaded_at']);
        }

        // Check regulation order records extract
        $rorRows = $metabaseConnection->fetchAllAssociative('SELECT * FROM analytics_regulation_order_record');
        $this->assertGreaterThanOrEqual(1, \count($rorRows));
        $this->assertEqualsCanonicalizing(
            ['id', 'uploaded_at', 'record_uuid', 'organization_uuid', 'status', 'category', 'subject', 'source', 'created_at', 'publication_date', 'start_date', 'end_date', 'is_permanent', 'validity_status'],
            array_keys($rorRows[0]),
        );

        // Execute again to test for uuid conflicts
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}

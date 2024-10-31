<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241016130817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure all measures have at least one period, using the existing regulation dates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO period (uuid, measure_uuid, start_datetime, end_datetime, recurrence_type)
            SELECT
                uuid_generate_v4() AS uuid,
                m.uuid as measure_uuid,
                ro.start_date AS start_datetime,
                ro.end_date + interval \'23 hours 59 minutes\' AS end_datetime,
                \'everyDay\' AS recurrence_type
            FROM measure AS m
            INNER JOIN regulation_order AS ro ON ro.uuid = m.regulation_order_uuid
            WHERE NOT EXISTS (SELECT 1 FROM period AS _p WHERE _p.measure_uuid = m.uuid)
        ', );

        $this->addSql(
            'UPDATE regulation_order SET category = :permanentCategory WHERE end_date IS NULL',
            ['permanentCategory' => 'permanentRegulation'],
        );

        $this->addSql('ALTER TABLE regulation_order DROP COLUMN start_date');
        $this->addSql('ALTER TABLE regulation_order DROP COLUMN end_date');
    }

    public function down(Schema $schema): void
    {
    }
}

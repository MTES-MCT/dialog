<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231031100605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate dailyrange data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->addSql('
            UPDATE period SET start_datetime = ro.start_date, end_datetime = ro.end_date
            FROM measure AS m
            INNER JOIN location AS l ON l.uuid = m.location_uuid
            INNER JOIN regulation_order AS ro ON ro.uuid = l.regulation_order_uuid
            WHERE ro.start_date <> NULL
            AND ro.end_date <> NULL
        ');
        $this->addSql('
            INSERT INTO dailyrange (uuid, applicable_days, period_uuid)
            SELECT public.uuid_generate_v4(), p.applicable_days, p.uuid
            FROM period AS p
            WHERE p.applicable_days <> NULL
        ');
        $this->addSql('
            INSERT INTO timeslot (uuid, start_time, end_time, daily_range_uuid)
            SELECT public.uuid_generate_v4(), p.start_time, p.end_time, d.uuid
            FROM dailyrange AS d
            INNER JOIN period AS p ON p.uuid = d.period_uuid
            WHERE p.start_time <> NULL
            AND p.end_time <> NULL
        ');

        $this->addSql('ALTER TABLE period ALTER applicable_days DROP NOT NULL');
        $this->addSql('ALTER TABLE period ALTER start_time DROP NOT NULL');
        $this->addSql('ALTER TABLE period ALTER end_time DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE period ALTER start_time SET NOT NULL');
        $this->addSql('ALTER TABLE period ALTER end_time SET NOT NULL');
        $this->addSql('ALTER TABLE period ALTER applicable_days SET NOT NULL');
    }
}

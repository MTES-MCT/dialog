<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240416135713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE period SET end_datetime = NULL
            FROM measure AS m
            JOIN regulation_order AS ro ON m.regulation_order_uuid = ro.uuid
            JOIN regulation_order_record AS roc ON roc.regulation_order_uuid = ro.uuid
            WHERE m.uuid = period.measure_uuid
            AND roc.source = :source
            ',
            ['source' => 'bacidf'],
        );
    }

    public function down(Schema $schema): void
    {
    }
}

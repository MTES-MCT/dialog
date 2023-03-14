<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230314135756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fill regulation_order start_date and end_date from any existing overall_period';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE regulation_order
            SET start_date = op.start_date
            FROM overall_period AS op
            JOIN regulation_condition AS rc ON op.regulation_condition_uuid = rc.uuid
            WHERE rc.regulation_order_uuid = regulation_order.uuid
        ');

        $this->addSql('
            UPDATE regulation_order
            SET end_date = op.end_date
            FROM overall_period AS op
            JOIN regulation_condition AS rc ON op.regulation_condition_uuid = rc.uuid
            WHERE rc.regulation_order_uuid = regulation_order.uuid
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE regulation_order SET start_date = NULL');
        $this->addSql('UPDATE regulation_order SET end_date = NULL');
    }
}

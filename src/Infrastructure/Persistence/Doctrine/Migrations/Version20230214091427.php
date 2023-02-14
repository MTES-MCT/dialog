<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230214091427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Swap foreign keys in regulation_order_record <-> regulation_order relationship';
    }

    public function up(Schema $schema): void
    {
        // Add new FK column.
        $this->addSql('ALTER TABLE regulation_order ADD regulation_order_record_uuid UUID');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_24FEED5DF598A94B ON regulation_order (regulation_order_record_uuid)');

        // Deactivate old FK constraint.
        $this->addSql('ALTER TABLE regulation_order_record DROP CONSTRAINT fk_528b2e6c267e0d5e');
        $this->addSql('DROP INDEX uniq_528b2e6c267e0d5e');

        // Swap FK column data.
        $this->addSql('UPDATE regulation_order SET regulation_order_record_uuid = (SELECT uuid FROM regulation_order_record WHERE regulation_order_uuid = regulation_order.uuid)');

        // RegulationOrder entities that did not have a corresponding RegulationOrderRecord are orphans. We can delete them.
        $this->addSql('DELETE FROM regulation_order WHERE regulation_order_record_uuid IS NULL');

        // Finish new FK constraint.
        $this->addSql('ALTER TABLE regulation_order ALTER regulation_order_record_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order ADD CONSTRAINT FK_24FEED5DF598A94B FOREIGN KEY (regulation_order_record_uuid) REFERENCES regulation_order_record (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Drop old FK column.
        $this->addSql('ALTER TABLE regulation_order_record DROP regulation_order_uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_record ADD regulation_order_uuid UUID');
        $this->addSql('CREATE UNIQUE INDEX uniq_528b2e6c267e0d5e ON regulation_order_record (regulation_order_uuid)');
        $this->addSql('ALTER TABLE regulation_order DROP CONSTRAINT FK_24FEED5DF598A94B');
        $this->addSql('DROP INDEX UNIQ_24FEED5DF598A94B');
        $this->addSql('UPDATE regulation_order_record SET regulation_order_uuid = (SELECT uuid FROM regulation_order WHERE regulation_order_record_uuid = regulation_order_record.uuid)');
        $this->addSql('ALTER TABLE regulation_order_record ALTER regulation_order_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order_record ADD CONSTRAINT fk_528b2e6c267e0d5e FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_order DROP regulation_order_record_uuid');
    }
}

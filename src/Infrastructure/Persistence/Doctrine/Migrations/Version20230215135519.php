<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230215135519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Swap FK in RegulationOrder <-> RegulationCondition relationship';
    }

    public function up(Schema $schema): void
    {
        // Drop old FK constraint
        $this->addSql('ALTER TABLE regulation_order DROP CONSTRAINT fk_24feed5d9f073263');
        $this->addSql('DROP INDEX uniq_24feed5d9f073263');

        // Create new FK column
        $this->addSql('ALTER TABLE regulation_condition ADD regulation_order_uuid UUID');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D8762B7267E0D5E ON regulation_condition (regulation_order_uuid)');

        // Move FK column data
        $this->addSql('UPDATE regulation_condition SET regulation_order_uuid = (SELECT uuid FROM regulation_order WHERE regulation_condition_uuid = regulation_condition.uuid)');
        $this->addSql('DELETE FROM regulation_condition WHERE regulation_order_uuid IS NULL');

        // Finalize new FK constraint
        $this->addSql('ALTER TABLE regulation_condition ALTER regulation_order_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B7267E0D5E FOREIGN KEY (regulation_order_uuid) REFERENCES regulation_order (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Drop old FK column
        $this->addSql('ALTER TABLE regulation_order DROP regulation_condition_uuid');
    }

    public function down(Schema $schema): void
    {
        // Drop new FK constraint
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B7267E0D5E');
        $this->addSql('DROP INDEX UNIQ_9D8762B7267E0D5E');

        // Create old FK column
        $this->addSql('ALTER TABLE regulation_order ADD regulation_condition_uuid UUID');
        $this->addSql('CREATE UNIQUE INDEX uniq_24feed5d9f073263 ON regulation_order (regulation_condition_uuid)');

        // Move FK column data
        $this->addSql('UPDATE regulation_order SET regulation_condition_uuid = (SELECT uuid FROM regulation_condition WHERE regulation_order_uuid = regulation_order.uuid)');
        $this->addSql('DELETE FROM regulation_order WHERE regulation_condition_uuid IS NULL');

        // Finalize old FK constraint
        $this->addSql('ALTER TABLE regulation_order ALTER regulation_condition_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE regulation_order ADD CONSTRAINT fk_24feed5d9f073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Drop new FK column
        $this->addSql('ALTER TABLE regulation_condition DROP regulation_order_uuid');
    }
}

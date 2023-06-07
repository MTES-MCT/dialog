<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230605140125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Condition';
    }

    public function up(Schema $schema): void
    {
        // Drop contents of VehicleCharacteristics, as it won't be used anymore for now (lonely table).
        $this->addSql('DELETE FROM vehicle_characteristics');

        // Unlink VehicleCharacteristics and Condition
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT fk_54f8f40a638b0587');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP COLUMN condition_uuid');

        // Unlink Period and Condition
        $this->addSql('ALTER TABLE period DROP CONSTRAINT fk_c5b81ece638b0587');

        // Unlink Measure and Condition
        $this->addSql('ALTER TABLE condition DROP CONSTRAINT fk_bdd6884396a61612');

        // Link Period to Measure
        $this->addSql('ALTER TABLE period ADD COLUMN measure_uuid UUID');
        $this->addSql('UPDATE period SET measure_uuid = c.measure_uuid FROM condition AS c WHERE c.uuid = period.condition_uuid');
        $this->addSql('ALTER TABLE period ALTER measure_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE96A61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C5B81ECE96A61612 ON period (measure_uuid)');

        // Drop Condition
        $this->addSql('ALTER TABLE period DROP COLUMN condition_uuid');
        $this->addSql('DROP TABLE condition');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_characteristics ADD COLUMN condition_uuid UUID NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_54f8f40a638b0587 ON vehicle_characteristics (condition_uuid)');

        $this->addSql('CREATE TABLE condition (uuid UUID NOT NULL, measure_uuid UUID NOT NULL, negate BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_bdd6884396a61612 ON condition (measure_uuid)');

        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT fk_54f8f40a638b0587 FOREIGN KEY (condition_uuid) REFERENCES condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE condition ADD CONSTRAINT fk_bdd6884396a61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE96A61612');
        $this->addSql('DROP INDEX IDX_C5B81ECE96A61612');
        $this->addSql('ALTER TABLE period ADD COLUMN condition_uuid UUID');
        $this->addSql('INSERT INTO condition VALUES (uuid_generate_v4(), m.uuid, false) FROM measure AS m');
        $this->addSql('UPDATE period SET condition_uuid = c.measure_uuid FROM condition AS c WHERE c.measure_uuid = period.measure_uuid');
        $this->addSql('ALTER TABLE period ALTER condition_uuid SET NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT fk_c5b81ece638b0587 FOREIGN KEY (condition_uuid) REFERENCES condition (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_c5b81ece638b0587 ON period (condition_uuid)');
        $this->addSql('ALTER TABLE period DROP COLUMN measure_uuid');
    }
}

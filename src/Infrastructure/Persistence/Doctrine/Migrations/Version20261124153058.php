<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124153058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stocke les localisations rejetées lors de l\'intégration d\'un arrêté via l\'API d\'écriture.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rejected_location (uuid UUID NOT NULL, regulation_order_record_uuid UUID NOT NULL, measure_index INT NOT NULL, location_index INT NOT NULL, location JSON NOT NULL, measure JSON NOT NULL, errors JSON NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_EC9D6C47F598A94B ON rejected_location (regulation_order_record_uuid)');
        $this->addSql('ALTER TABLE rejected_location ADD CONSTRAINT FK_EC9D6C47F598A94B FOREIGN KEY (regulation_order_record_uuid) REFERENCES regulation_order_record (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rejected_location');
    }
}

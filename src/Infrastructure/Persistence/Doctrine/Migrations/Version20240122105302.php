<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240122105302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_new ADD road_type VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE location_new ADD administrator VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE location_new ADD road_number VARCHAR(50) DEFAULT NULL');

        $this->addSql("UPDATE location_new SET road_type = 'lane'");
        $this->addSql('ALTER TABLE location_new ALTER road_type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location_new DROP road_type');
        $this->addSql('ALTER TABLE location_new DROP administrator');
        $this->addSql('ALTER TABLE location_new DROP road_number');
    }
}

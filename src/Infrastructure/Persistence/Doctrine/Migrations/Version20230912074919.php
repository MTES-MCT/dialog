<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230912074919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set ADD heavyweight_max_weight DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle_set ADD heavyweight_max_width DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle_set ADD heavyweight_max_length DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle_set ADD heavyweight_max_height DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set DROP heavyweight_max_weight');
        $this->addSql('ALTER TABLE vehicle_set DROP heavyweight_max_width');
        $this->addSql('ALTER TABLE vehicle_set DROP heavyweight_max_length');
        $this->addSql('ALTER TABLE vehicle_set DROP heavyweight_max_height');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230404085811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD address VARCHAR(255)');
        $this->addSql('UPDATE location SET address = concat(road_name, \' \', postal_code, \' \', city)');
        $this->addSql('ALTER TABLE location ALTER address SET NOT NULL');
        $this->addSql('ALTER TABLE location DROP postal_code');
        $this->addSql('ALTER TABLE location DROP city');
        $this->addSql('ALTER TABLE location DROP road_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD postal_code VARCHAR(5)');
        $this->addSql('UPDATE location SET postal_code = \'N/C\'');
        $this->addSql('ALTER TABLE location ALTER postal_code SET NOT NULL');

        $this->addSql('ALTER TABLE location ADD city VARCHAR(255)');
        $this->addSql('UPDATE location SET city = \'N/C\'');
        $this->addSql('ALTER TABLE location ALTER city SET NOT NULL');

        $this->addSql('ALTER TABLE location ADD road_name VARCHAR(60)');
        $this->addSql('UPDATE location SET road_name = \'N/C\'');
        $this->addSql('ALTER TABLE location ALTER road_name SET NOT NULL');

        $this->addSql('ALTER TABLE location DROP address');
    }
}

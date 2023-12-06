<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231206135155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split address';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER COLUMN address DROP NOT NULL;');

        $this->addSql('ALTER TABLE location ADD city_code VARCHAR(5)');
        $this->addSql('ALTER TABLE location ADD city_label VARCHAR(255)');
        $this->addSql('ALTER TABLE location ADD road_name VARCHAR(255)');

        // TODO: how to get city_code? Call API Address? Prepare offline configurable mapping file?
        $this->addSql("UPDATE location SET city_label = substring(address from '\d{5} (.+)\$');");
        $this->addSql("UPDATE location SET road_name = substring(address from '^([^\d,]+),? \d{5}');");

        // TODO
        // $this->addSql('ALTER TABLE location ALTER COLUMN city_code SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER COLUMN city_label SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER COLUMN road_name SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP city_code');
        $this->addSql('ALTER TABLE location DROP city_label');
        $this->addSql('ALTER TABLE location DROP road_name');
    }
}

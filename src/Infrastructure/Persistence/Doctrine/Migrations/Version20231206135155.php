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
        $this->addSql('ALTER TABLE location ADD city_name VARCHAR(255)');
        $this->addSql('ALTER TABLE location ADD city_postcode VARCHAR(255)');
        $this->addSql('ALTER TABLE location ADD road_name VARCHAR(255)');

        $this->addSql("UPDATE location SET road_name = substring(address from '^([^\d,]+),? \d{5}');");
        $this->addSql("UPDATE location SET city_name = substring(address from '\d{5} (.+)\$');");
        $this->addSql("UPDATE location SET city_postcode = substring(address from '(\d{5}) .+\$');");
        $this->addSql("
            UPDATE location SET city_code = (
                CASE
                    WHEN city_name = 'Paris' THEN '751' || substring(address from '750(\d{2}) .+\$')
                    WHEN city_name = 'Marseille' THEN '132' || substring(address from '130(\d{2}) .+\$')
                    WHEN city_name = 'Lyon' THEN '6938' || substring(address from '6900(\d) .+\$')
                    ELSE (
                        SELECT insee_code FROM fr_city AS c
                        WHERE c.departement = substring(address from '(\d{2})\d{3} .+\$')
                        AND lower(c.name) = lower(city_name)
                    )
                END
            )
        ");

        $this->addSql('ALTER TABLE location ADD city_label VARCHAR(255)');
        $this->addSql("UPDATE location SET city_label = city_name || ' (' || city_postcode || ')'");
        $this->addSql('ALTER TABLE location DROP city_name');
        $this->addSql('ALTER TABLE location DROP city_postcode');

        $this->addSql('ALTER TABLE location ALTER COLUMN city_code SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER COLUMN city_label SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP city_code');
        $this->addSql('ALTER TABLE location DROP city_label');
        $this->addSql('ALTER TABLE location DROP road_name');
        $this->addSql('ALTER TABLE location ALTER COLUMN address SET NOT NULL');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231218154722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO location_new (uuid, city_label, road_name, city_code, from_house_number, to_house_number, geometry, measure_uuid)
            SELECT public.uuid_generate_v4(), l.city_label, l.road_name, l.city_code, l.from_house_number, l.to_house_number, l.geometry, m.uuid
            FROM location AS l
            INNER JOIN measure AS m ON l.uuid = m.location_uuid
        ');
    }

    public function down(Schema $schema): void
    {
    }
}

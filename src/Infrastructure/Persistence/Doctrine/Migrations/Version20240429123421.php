<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240429123421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE named_street ALTER city_code SET NOT NULL');
        $this->addSql('ALTER TABLE named_street ALTER city_label SET NOT NULL');
        $this->addSql('ALTER TABLE named_street ALTER road_name SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER administrator SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER road_number SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER from_point_number SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER from_side SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER to_point_number SET NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER to_side SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE numbered_road ALTER administrator DROP NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER road_number DROP NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER from_point_number DROP NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER from_side DROP NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER to_point_number DROP NOT NULL');
        $this->addSql('ALTER TABLE numbered_road ALTER to_side DROP NOT NULL');
        $this->addSql('ALTER TABLE named_street ALTER city_code DROP NOT NULL');
        $this->addSql('ALTER TABLE named_street ALTER city_label DROP NOT NULL');
        $this->addSql('ALTER TABLE named_street ALTER road_name DROP NOT NULL');
    }
}

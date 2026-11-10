<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le sens de circulation au tracé libre (raw_geojson.direction), double sens par défaut.';
    }

    public function up(Schema $schema): void
    {
        // Même démarche que pour named_street et numbered_road : colonne nullable, remplissage, puis NOT NULL sans défaut
        $this->addSql('ALTER TABLE raw_geojson ADD direction VARCHAR(10) DEFAULT NULL');
        $this->addSql("UPDATE raw_geojson SET direction = 'BOTH'");
        $this->addSql('ALTER TABLE raw_geojson ALTER direction SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE raw_geojson DROP direction');
    }
}

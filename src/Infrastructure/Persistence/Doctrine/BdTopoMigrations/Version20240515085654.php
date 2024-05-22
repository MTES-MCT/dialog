<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240515085654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX voie_nommee_id_pseudo_fpb_idx ON voie_nommee (id_pseudo_fpb)');
        $this->addSql('CREATE INDEX troncon_de_route_identifiant_voie_1_gauche_idx ON troncon_de_route (identifiant_voie_1_gauche)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS voie_nommee_id_pseudo_fpb_idx');
        $this->addSql('DROP INDEX IF EXISTS troncon_de_route_identifiant_voie_1_gauche_idx');
    }
}

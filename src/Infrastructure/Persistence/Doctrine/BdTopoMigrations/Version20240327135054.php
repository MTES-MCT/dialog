<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327135054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on point_de_repere table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX point_de_repere_route_numero_gestionnaire_cote_idx ON point_de_repere (route, numero, gestionnaire, cote);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS point_de_repere_route_numero_gestionnaire_cote_idx');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230711144619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES
                ('5c19be02-b57d-4059-8712-de80219cbe4e', 'Mairie de Meynes')
        ");

        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'f1d4613f-0935-4fc7-a989-982079a72137\', \'Nathalie Meheni-Duverlie\', \'accueil@meynes.fr\', \'$2y$13$gfJ7NUHunME7N.HuI8cq6emSiohy/lo93/SvBITE6Fa6kalUimGMy\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('f1d4613f-0935-4fc7-a989-982079a72137', '5c19be02-b57d-4059-8712-de80219cbe4e')
        ");
    }
}

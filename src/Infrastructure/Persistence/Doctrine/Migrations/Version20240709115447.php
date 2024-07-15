<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240709115447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD roles TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".roles IS \'(DC2Type:array)\'');
        $this->addSql('UPDATE "user" SET roles = \'a:1:{i:0;s:9:"ROLE_USER";}\';');
        $this->addSql('UPDATE "user" SET roles = \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\' WHERE email = \'mathieu.fernandez@beta.gouv.fr\';');
        $this->addSql('ALTER TABLE "user" ALTER roles SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP roles');
    }
}

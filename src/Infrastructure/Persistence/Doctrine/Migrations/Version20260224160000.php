<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace organization roles with isOwner boolean flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users ADD is_owner BOOLEAN NOT NULL DEFAULT false');
        $this->addSql("UPDATE organizations_users SET is_owner = true WHERE roles LIKE '%ROLE_ORGA_ADMIN%'");
        $this->addSql('ALTER TABLE organizations_users ALTER COLUMN is_owner DROP DEFAULT');
        $this->addSql('ALTER TABLE organizations_users DROP COLUMN roles');

        $this->addSql('ALTER TABLE invitation DROP COLUMN role');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users ADD roles TEXT NOT NULL DEFAULT \'a:1:{i:0;s:22:"ROLE_ORGA_CONTRIBUTOR";}\'');
        $this->addSql("UPDATE organizations_users SET roles = 'a:1:{i:0;s:15:\"ROLE_ORGA_ADMIN\";}' WHERE is_owner = true");
        $this->addSql("UPDATE organizations_users SET roles = 'a:1:{i:0;s:22:\"ROLE_ORGA_CONTRIBUTOR\";}' WHERE is_owner = false");
        $this->addSql('ALTER TABLE organizations_users DROP COLUMN is_owner');

        $this->addSql('ALTER TABLE invitation ADD role VARCHAR(25) NOT NULL DEFAULT \'ROLE_ORGA_CONTRIBUTOR\'');
    }
}

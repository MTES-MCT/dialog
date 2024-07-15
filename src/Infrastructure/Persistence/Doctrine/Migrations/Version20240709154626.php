<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240709154626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68E8766E3B');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT FK_9328CA68ABFE1C6F');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT organizations_users_pkey');
        $this->addSql('ALTER TABLE organizations_users ADD uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE organizations_users ADD roles TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organizations_users.roles IS \'(DC2Type:array)\'');

        $this->addSql('UPDATE "organizations_users" SET uuid = public.uuid_generate_v4();');
        $this->addSql('UPDATE "organizations_users" SET roles = \'a:1:{i:0;s:21:"ROLE_ORGA_CONTRIBUTOR";}\';');

        $this->addSql('ALTER TABLE organizations_users ALTER uuid SET NOT NULL');
        $this->addSql('ALTER TABLE organizations_users ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68E8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT FK_9328CA68ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD PRIMARY KEY (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT fk_9328ca68abfe1c6f');
        $this->addSql('ALTER TABLE organizations_users DROP CONSTRAINT fk_9328ca68e8766e3b');
        $this->addSql('DROP INDEX organizations_users_pkey');
        $this->addSql('ALTER TABLE organizations_users DROP uuid');
        $this->addSql('ALTER TABLE organizations_users DROP roles');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT fk_9328ca68abfe1c6f FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD CONSTRAINT fk_9328ca68e8766e3b FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations_users ADD PRIMARY KEY (organization_uuid, user_uuid)');
    }
}

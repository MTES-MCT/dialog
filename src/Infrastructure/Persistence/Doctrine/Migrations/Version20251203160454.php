<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203160454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, roles, is_verified) VALUES
                (\'22c4e915-c0b7-40ab-bd64-ff2e5ae2f737\', \'Heloise GEORGEAULT\', \'heloise.georgeault@beta.gouv.fr\', \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\', \'true\')
        ');

        $this->addSql('
            INSERT INTO "password_user" (uuid, user_uuid, password) VALUES
                (\'349b6a1e-68e2-4555-957e-7d305753c0d1\', \'22c4e915-c0b7-40ab-bd64-ff2e5ae2f737\', \'$2y$13$iEBqDWvluESOxB9ygqiPW.VwhDDgjnRp8B3.u0R0fWK3/QyDjhthC\')
        ');

        $this->addSql('
            INSERT INTO organizations_users (uuid, user_uuid, organization_uuid, roles) VALUES
                (\'a0f7ca3c-fafa-43d3-aef4-2a066bde8e3e\', \'22c4e915-c0b7-40ab-bd64-ff2e5ae2f737\', \'e0d93630-acf7-4722-81e8-ff7d5fa64b66\', \'a:1:{i:0;s:15:"ROLE_ORGA_ADMIN";}\')
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

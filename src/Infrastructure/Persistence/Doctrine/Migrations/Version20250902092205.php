<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902092205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email,roles,is_verified) VALUES
                (\'b7fa68ba-5443-4161-8ec8-73e72c17992d\', \'Eglantine SCHMITT\', \'eglantine.schmitt@beta.gouv.fr\', \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\',\'true\')
        ');
        $this->addSql('
            INSERT INTO "password_user" (uuid, user_uuid, password) VALUES
                (\'613bedae-a7e0-4c1d-b2f4-4175a2d9671f\', \'b7fa68ba-5443-4161-8ec8-73e72c17992d\', \'$2y$13$FGzLfRBW0sGZqn/eh3hlBO/nQI4XZKzrzI1svPbCYUKWlRrQELCPm\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (uuid, user_uuid, organization_uuid, roles) VALUES
                ('64f38057-9a36-4bda-ae39-b012e87d2b43','b7fa68ba-5443-4161-8ec8-73e72c17992d', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66', 'a:1:{i:0;s:15:\"ROLE_ORGA_ADMIN\";}')
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

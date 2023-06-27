<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626093821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organizations and associates them to Mathieu Fernandez account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES
                ('8d0b2d31-197e-4ab3-b548-ad7b869b32c8', 'Ville de Marseille'),
                ('cf27c9dd-655e-4c5b-95bc-cd867939a5cd', 'Ville de Nice'),
                ('a41e2e19-51b4-4f6d-8a5a-abb68f0ef648', 'Mairie de Toulouse')
        ");

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('6586e9e8-dcba-4298-90f4-e557d5222100', '8d0b2d31-197e-4ab3-b548-ad7b869b32c8'),
                ('6586e9e8-dcba-4298-90f4-e557d5222100', 'cf27c9dd-655e-4c5b-95bc-cd867939a5cd'),
                ('6586e9e8-dcba-4298-90f4-e557d5222100', 'a41e2e19-51b4-4f6d-8a5a-abb68f0ef648')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}

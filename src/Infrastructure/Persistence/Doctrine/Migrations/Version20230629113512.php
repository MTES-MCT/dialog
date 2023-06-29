<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230629113512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
        INSERT INTO organization (uuid, name) VALUES
            ('99311144-0907-4d65-9490-2be9b18e2ed0', 'Mairie de Fronton'),
            ('34115215-1749-4387-bf19-b09c3d4a00d0', 'Mairie de Flassans-sur-Issole')
    ");

        $this->addSql('
        INSERT INTO "user" (uuid, full_name, email, password) VALUES
            (\'7e609c52-0037-4d33-994c-ee301cd3e31e\', \'Lucas Molli\', \'communication@flassans.fr\', \'$2y$13$zk.ECR08nspNKbfCdTtQRuiSA2pqBWq3SyNHm3tBfUuFs9c4VXXbS\'),
            (\'4145a146-b25f-4c19-b46f-72e860b0bbaf\', \'Julien Candeil\', \'cdspolice@mairie-fronton.fr\', \'$2y$13$po8fBA7CkdWZTzWWIW5muOrTzSQ4GkH7R4DEwwlhJKkDWdqUKQJnq\')
    ');

        $this->addSql("
        INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
            ('7e609c52-0037-4d33-994c-ee301cd3e31e', '34115215-1749-4387-bf19-b09c3d4a00d0'),
            ('4145a146-b25f-4c19-b46f-72e860b0bbaf', '99311144-0907-4d65-9490-2be9b18e2ed0')
    ");
    }

    public function down(Schema $schema): void
    {
    }
}

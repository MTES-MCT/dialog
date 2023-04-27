<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230426173233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creation Christophe Perez account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO organization (uuid, name) VALUES ('1da4f916-ae52-4908-b154-d99468911719', 'Nailloux')
        ");

        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'4596828d-93fc-423d-88c9-67e638539f4e\', \'Christophe Perez\', \'policemunicipale@mairienailloux31.com\', \'$2y$13$L3RQjcOqxX4fdRJoAb5sFOUQa98yQBvuSjCP3gAjyGGxTYEgM72le\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('4596828d-93fc-423d-88c9-67e638539f4e', '1da4f916-ae52-4908-b154-d99468911719')
        ");
    }

    public function down(Schema $schema): void
    {
    }
}

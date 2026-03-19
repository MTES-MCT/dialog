<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250926120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_uuid to api_client for linking API keys to users (nullable for migration of existing keys)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_client ADD user_uuid UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_41B343D5ABFE1C6F ON api_client (user_uuid)');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D5ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES "user" (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D5ABFE1C6F');
        $this->addSql('DROP INDEX IDX_41B343D5ABFE1C6F');
        $this->addSql('ALTER TABLE api_client DROP user_uuid');
    }
}

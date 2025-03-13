<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313103531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Johan RICHER
        $this->addSql('DELETE FROM organizations_users WHERE user_uuid = \'0e7c201f-9a25-4a04-8357-468935f239f6\'');
        $this->addSql('DELETE FROM "user" WHERE uuid = \'0e7c201f-9a25-4a04-8357-468935f239f6\'');

        // Anne-Sophie TRANCHET
        $this->addSql('DELETE FROM organizations_users WHERE user_uuid = \'9f693c3c-6ed9-4ee5-b725-3d193e29ddd3\'');
        $this->addSql('DELETE FROM "user" WHERE uuid = \'9f693c3c-6ed9-4ee5-b725-3d193e29ddd3\'');

        // Julien JACQUELINET
        $this->addSql('DELETE FROM organizations_users WHERE user_uuid = \'13ad7a59-bd3c-4e1d-8f85-8b148ad00705\'');
        $this->addSql('DELETE FROM "user" WHERE uuid = \'13ad7a59-bd3c-4e1d-8f85-8b148ad00705\'');
    }

    public function down(Schema $schema): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230615071803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, password) VALUES
                (\'0e7c201f-9a25-4a04-8357-468935f239f6\', \'Johan RICHER\', \'johan.richer@multi.coop\', \'$2y$13$EoZuOUBFJ/ZfIhcw/0MywOtcARXC//5nhNjfHNsuIft1Bf.gsN/6G\')
        ');

        $this->addSql("
            INSERT INTO organizations_users (user_uuid, organization_uuid) VALUES
                ('0e7c201f-9a25-4a04-8357-468935f239f6', 'e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ");
    }
}

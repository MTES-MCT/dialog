<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407092015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE regulation_order_template (uuid UUID NOT NULL, organization_uuid UUID DEFAULT NULL, name VARCHAR(150) NOT NULL, title VARCHAR(150) NOT NULL, visa_content TEXT NOT NULL, considering_content TEXT NOT NULL, article_content TEXT NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,PRIMARY KEY(uuid))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8A14CAD6E8766E3B ON regulation_order_template (organization_uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order_template ADD CONSTRAINT FK_8A14CAD6E8766E3B FOREIGN KEY (organization_uuid) REFERENCES organization (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order_template DROP CONSTRAINT FK_8A14CAD6E8766E3B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE regulation_order_template
        SQL);
    }
}

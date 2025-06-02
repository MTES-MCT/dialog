<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527115255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD regulation_order_template_uuid UUID DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order ADD CONSTRAINT FK_24FEED5D495CDF4B FOREIGN KEY (regulation_order_template_uuid) REFERENCES regulation_order_template (uuid) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_24FEED5D495CDF4B ON regulation_order (regulation_order_template_uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP CONSTRAINT FK_24FEED5D495CDF4B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_24FEED5D495CDF4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE regulation_order DROP regulation_order_template_uuid
        SQL);
    }
}

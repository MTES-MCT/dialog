<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230706154140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_24FEED5D845CBB3E ON regulation_order (end_date)');
        $this->addSql('CREATE INDEX IDX_528B2E6C7B00651C ON regulation_order_record (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_24FEED5D845CBB3E');
        $this->addSql('DROP INDEX IDX_528B2E6C7B00651C');
    }
}

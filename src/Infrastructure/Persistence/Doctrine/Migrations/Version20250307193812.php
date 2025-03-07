<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307193812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization ALTER code TYPE VARCHAR(10)');
        $this->addSql('ALTER TABLE organization ALTER code_type TYPE VARCHAR(15)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization ALTER code TYPE VARCHAR(5)');
        $this->addSql('ALTER TABLE organization ALTER code_type TYPE VARCHAR(10)');
    }
}

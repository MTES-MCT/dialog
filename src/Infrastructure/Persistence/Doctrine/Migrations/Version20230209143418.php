<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230209143418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE regulation_order_record SET organization_uuid = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66'");
        $this->addSql('ALTER TABLE regulation_order_record ALTER organization_uuid SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order_record ALTER organization_uuid DROP NOT NULL');
    }
}

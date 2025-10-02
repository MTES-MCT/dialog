<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002132133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_order_history ALTER regulation_order_uuid TYPE UUID USING regulation_order_uuid::uuid');
        $this->addSql('ALTER TABLE regulation_order_history ALTER user_uuid TYPE UUID USING user_uuid::uuid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE regulation_order_history ALTER regulation_order_uuid TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE regulation_order_history ALTER user_uuid TYPE VARCHAR(50)');
    }
}

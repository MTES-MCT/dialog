<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250324150412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measure ALTER type TYPE VARCHAR(17)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measure ALTER type TYPE VARCHAR(16)');
    }
}

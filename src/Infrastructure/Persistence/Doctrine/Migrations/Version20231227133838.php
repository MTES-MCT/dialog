<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231227133838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix road_name extraction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE location SET road_name = substring(address from '^(.+),? \d{5}') WHERE road_name IS NULL AND geometry IS NOT NULL;");
    }

    public function down(Schema $schema): void
    {
    }
}

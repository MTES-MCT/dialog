<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250303141154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE numbered_road ADD from_department_code VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE numbered_road ADD to_department_code VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE numbered_road DROP from_department_code');
        $this->addSql('ALTER TABLE numbered_road DROP to_department_code');
    }
}

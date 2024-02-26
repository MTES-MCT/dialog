<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240118150323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set ALTER other_exempted_type_text TYPE VARCHAR(300)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_set ALTER other_exempted_type_text TYPE VARCHAR(100)');
    }
}

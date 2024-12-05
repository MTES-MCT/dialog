<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241205151339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE numbered_road ADD direction VARCHAR(10) DEFAULT NULL;');
        $this->addSql("UPDATE numbered_road SET direction = 'BOTH';");
        $this->addSql('ALTER TABLE numbered_road ALTER direction SET NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE numbered_road DROP direction');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230329092116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Location house numbers and points become nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER from_house_number DROP NOT NULL');
        $this->addSql('ALTER TABLE location ALTER from_point DROP NOT NULL');
        $this->addSql('ALTER TABLE location ALTER to_house_number DROP NOT NULL');
        $this->addSql('ALTER TABLE location ALTER to_point DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ALTER from_house_number SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER from_point SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER to_house_number SET NOT NULL');
        $this->addSql('ALTER TABLE location ALTER to_point SET NOT NULL');
    }
}

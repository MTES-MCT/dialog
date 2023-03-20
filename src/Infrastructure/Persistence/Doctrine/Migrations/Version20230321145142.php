<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230321145142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM regulation_condition');
        $this->addSql('ALTER TABLE regulation_condition ADD measure_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B796A61612 FOREIGN KEY (measure_uuid) REFERENCES measure (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9D8762B796A61612 ON regulation_condition (measure_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B796A61612');
        $this->addSql('DROP INDEX IDX_9D8762B796A61612');
        $this->addSql('ALTER TABLE regulation_condition DROP measure_uuid');
    }
}

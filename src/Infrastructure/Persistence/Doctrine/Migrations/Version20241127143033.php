<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241127143033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order ADD subject VARCHAR(50) DEFAULT NULL');

        $this->addSql(
            'UPDATE regulation_order SET subject = :roadMaintenanceCategory WHERE category = :roadMaintenanceCategory ',
            ['roadMaintenanceCategory' => 'roadMaintenance'],
        );

        $this->addSql(
            'UPDATE regulation_order SET subject = :incidentCategory WHERE category = :incidentCategory ',
            ['incidentCategory' => 'incident'],
        );

        $this->addSql(
            'UPDATE regulation_order SET subject = :eventCategory WHERE category = :eventCategory ',
            ['eventCategory' => 'event'],
        );

        $this->addSql(
            'UPDATE regulation_order SET subject = :otherCategory WHERE category = :otherCategory ',
            ['otherCategory' => 'other'],
        );

        $this->addSql(
            'UPDATE regulation_order SET category = :temporaryCategory WHERE category <> :permanentCategory ',
            [
                'temporaryCategory' => 'temporaryRegulation',
                'permanentCategory' => 'permanentRegulation',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regulation_order DROP subject');
    }
}

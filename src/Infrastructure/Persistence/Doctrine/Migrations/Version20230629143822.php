<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230629143822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE "user" set email = \'christian.melis6@orange.fr\' WHERE uuid=\'4418eb23-a469-4495-8e97-213491eca33a\'
        ');
    }

    public function down(Schema $schema): void
    {
    }
}

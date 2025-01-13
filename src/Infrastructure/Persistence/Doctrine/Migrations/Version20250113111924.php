<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250113111924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO password_user (uuid, user_uuid, password)
            SELECT uuid_generate_v4() AS uuid, u.uuid as user_uuid, u.password as password FROM public.user AS u
        ');
    }

    public function down(Schema $schema): void
    {
    }
}

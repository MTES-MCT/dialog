<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309160758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dev user Julien Zamor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, roles, is_verified) VALUES
                (\'e4c37849-c370-434b-be08-5234982ab683\', \'Julien ZAMOR\', \'julien.zamor.ext@beta.gouv.fr\', \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\', \'true\')
        ');

        $this->addSql('
            INSERT INTO "password_user" (uuid, user_uuid, password) VALUES
                (\'f3dfd18f-5bd8-4acc-8b95-18c4012d8017\', \'e4c37849-c370-434b-be08-5234982ab683\', \'$2y$12$ExzyAkzA1KHtHmFdycsm2uaTWcwHmT45wG38pKUIYowkzs5PVoibG\')
        ');

        $this->addSql('
            INSERT INTO organizations_users (uuid, user_uuid, organization_uuid, is_owner) VALUES
                (\'97e30b18-e595-4b1a-b5f4-5e7901ce3a0b\', \'e4c37849-c370-434b-be08-5234982ab683\', \'e0d93630-acf7-4722-81e8-ff7d5fa64b66\', \'true\')
        ');
    }

    public function down(Schema $schema): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260114160454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dev user Antoine Smagghe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO "user" (uuid, full_name, email, roles, is_verified) VALUES
                (\'f933a66b-f25c-43a5-a9e2-b408d0832258\', \'Antoine SMAGGHE\', \'antoine.smagghe.ext@beta.gouv.fr\', \'a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}\', \'true\')
        ');

        $this->addSql('
            INSERT INTO "password_user" (uuid, user_uuid, password) VALUES
                (\'48e8ea2b-a739-4dd2-9111-ab1a2b267620\', \'f933a66b-f25c-43a5-a9e2-b408d0832258\', \'$2y$13$YDP63pb4dq51RnaFH7XcXeU1URJosZALl/fErMLafUio7y3ZBkluO\')
        ');

        $this->addSql('
            INSERT INTO organizations_users (uuid, user_uuid, organization_uuid, roles) VALUES
                (\'d453cc3f-93b8-457a-bbbc-4b9b0cfc4b54\', \'f933a66b-f25c-43a5-a9e2-b408d0832258\', \'e0d93630-acf7-4722-81e8-ff7d5fa64b66\', \'a:1:{i:0;s:15:"ROLE_ORGA_ADMIN";}\')
        ');
    }

    public function down(Schema $schema): void
    {
    }
}

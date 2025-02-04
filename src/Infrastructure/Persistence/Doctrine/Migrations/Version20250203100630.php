<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250203100630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO regulation_order_history (uuid, regulation_order_uuid, user_uuid, action, date) 
            SELECT
                public.uuid_generate_v4(),
                ro.uuid AS regulation_order_uuid,
                u.uuid AS user_uuid,
                \'create\' AS action,
                ror.created_at AS date
            FROM regulation_order_record AS ror
            INNER JOIN regulation_order AS ro ON ro.uuid = ror.regulation_order_uuid
            INNER JOIN organization AS o ON o.uuid = ror.organization_uuid
            INNER JOIN organizations_users AS ou ON ou.organization_uuid = o.uuid
              INNER JOIN "user" AS u ON ou.user_uuid = u.uuid
            WHERE NOT EXISTS (SELECT 1 FROM regulation_order_history AS roh WHERE CAST(roh.regulation_order_uuid AS uuid) = ro.uuid)
            AND ou.roles LIKE \'%ROLE_ORGA_ADMIN%\';
        ', );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

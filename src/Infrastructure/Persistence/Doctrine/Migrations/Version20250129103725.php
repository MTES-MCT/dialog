<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250129103725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD storage_area_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBFB2160B6 FOREIGN KEY (storage_area_uuid) REFERENCES storage_area (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5E9E89CBFB2160B6 ON location (storage_area_uuid)');

        $this->addSql('ALTER TABLE storage_area DROP CONSTRAINT fk_deb9f67d517be5e6');
        $this->addSql('DROP INDEX idx_deb9f67d517be5e6');
        $this->addSql('ALTER TABLE storage_area DROP location_uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_area ADD location_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE storage_area ADD CONSTRAINT fk_deb9f67d517be5e6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_deb9f67d517be5e6 ON storage_area (location_uuid)');

        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CBFB2160B6');
        $this->addSql('DROP INDEX IDX_5E9E89CBFB2160B6');
        $this->addSql('ALTER TABLE location DROP storage_area_uuid');
    }
}

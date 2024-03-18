<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240318140810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set up initial BD TOPO indexes';
    }

    public function up(Schema $schema): void
    {
        // We can't use the built-in unaccent() in an index because it is not IMMUTABLE, only STABLE.
        // This is because the dictionary can theoretically change.
        // If we commit to never changing the dictionary (likely), we can use a custom IMMUTABLE wrapper.
        // Inspired by:
        // * The "Best for now" Postgres 14+ solution here: https://stackoverflow.com/a/11007216
        // * And this: https://peterullrich.com/unaccented-name-search-with-postgres-and-ecto
        $this->addSql('CREATE EXTENSION unaccent');
        $this->addSql('CREATE OR REPLACE FUNCTION public.f_unaccent(text)
            RETURNS text
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT public.unaccent(\'public.unaccent\', $1)
            $func$;
        ');

        $this->addSql('CREATE OR REPLACE FUNCTION public.f_bdtopo_voie_nommee_normalize_nom_minuscule(text)
            returns TEXT
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT lower(translate(f_unaccent($1), \'-’\', \' \'\'\'));
            $func$;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS public.f_bdtopo_voie_nommee_normalize_nom_minuscule');
        $this->addSql('DROP FUNCTION IF EXISTS public.f_unaccent');
        $this->addSql('DROP EXTENSION unaccent');
    }
}

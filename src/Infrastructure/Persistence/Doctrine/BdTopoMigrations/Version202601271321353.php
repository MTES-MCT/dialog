<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202601271321353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fonction de normalisation des accents';
    }

    public function up(Schema $schema): void
    {
        // Fonction de normalisation des accents pour la recherche de noms de voies
        // Normalise les caractères accentués français (majuscules et minuscules)
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.f_normalize_accents(text)
            RETURNS text
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT LOWER(TRIM(translate($1,
                    'àáâãäåèéêëìíîïòóôõöùúûüýÿçñÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝŸÇÑ',
                    'aaaaaaeeeeiiiiooooouuuuyycnAAAAAAEEEEIIIIOOOOOUUUUYYCN'
                )));
            $func$;
            SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS public.f_normalize_accents');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:bdtopo:setup_indexes',
    description: 'Create BD TOPO indexes, extensions, and functions (equivalent to Version20250522085224 migration)',
)]
class BdTopoSetupIndexesCommand extends Command
{
    public function __construct(
        private Connection $bdtopo2025Connection,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $statements = [
            'Extension postgis' => 'CREATE EXTENSION IF NOT EXISTS postgis',
            'Index route_numerotee_ou_nommee' => 'CREATE INDEX IF NOT EXISTS route_numerotee_ou_nommee_numero_gestionnaire_idx ON route_numerotee_ou_nommee (numero, gestionnaire);',
            'Index point_de_repere (route)' => 'CREATE INDEX IF NOT EXISTS point_de_repere_route_numero_gestionnaire_cote_idx ON point_de_repere (route, numero, gestionnaire, cote);',
            'Index voie_nommee (identifiant_voie_ban)' => 'CREATE INDEX IF NOT EXISTS voie_nommee_identifiant_voie_ban_idx ON voie_nommee (identifiant_voie_ban)',
            'Index voie_nommee (insee_commune)' => 'CREATE INDEX IF NOT EXISTS voie_nommee_insee_commune_idx ON voie_nommee (insee_commune)',
            'Index troncon_de_route' => 'CREATE INDEX IF NOT EXISTS troncon_de_route_identifiant_voie_ban_gauche_idx ON troncon_de_route (identifiant_voie_ban_gauche)',
            'Extension postgis_sfcgal' => 'CREATE EXTENSION IF NOT EXISTS postgis_sfcgal',
            'Index point_de_repere (identifiant_de_section)' => 'CREATE INDEX IF NOT EXISTS point_de_repere_identifiant_de_section_idx ON point_de_repere (identifiant_de_section);',
            'Function f_ST_NormalizeGeometryCollection' => <<<'SQL'
                CREATE OR REPLACE FUNCTION public.f_ST_NormalizeGeometryCollection(geometry)
                RETURNS geometry
                LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
                AS $func$
                    SELECT CASE
                        -- Les GeometryCollection à 1 seul élément ne sont pas conformes à la spec GeoJSON.
                        WHEN ST_GeometryType($1) = 'ST_GeometryCollection' AND ST_NumGeometries($1) = 1
                            -- ST_CollectionHomogenize('GEOMETRYCOLLECTION(POINT(0, 0))') renvoie 'POINT(0, 0)'
                            -- https://postgis.net/docs/ST_CollectionHomogenize.html
                            THEN ST_CollectionHomogenize($1)
                        ELSE $1
                    END
                $func$;
                SQL,
            'Function f_normalize_accents' => <<<'SQL'
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
        ];

        $io = new SymfonyStyle($input, $output);

        $progressBar = new ProgressBar($output, \count($statements));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('');
        $progressBar->start();

        $errors = [];

        foreach ($statements as $label => $sql) {
            $progressBar->setMessage($label);

            try {
                $this->bdtopo2025Connection->executeStatement($sql);
            } catch (DBALException $e) {
                $errors[$label] = $e->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Done');
        $progressBar->finish();
        $output->writeln('');

        if (\count($errors) > 0) {
            $io->error(\sprintf('%d statement(s) failed:', \count($errors)));

            foreach ($errors as $label => $message) {
                $io->writeln(\sprintf('  - <comment>%s</comment>: %s', $label, $message));
            }

            return Command::FAILURE;
        }

        $io->success('BD TOPO indexes, extensions, and functions created successfully.');

        return Command::SUCCESS;
    }
}

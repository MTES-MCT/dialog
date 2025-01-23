<?php

declare(strict_types=1);

namespace App\Infrastructure\Data\StorageArea;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use Doctrine\DBAL\Connection;

class StorageAreaMigrationGenerator
{
    public function __construct(
        private readonly Connection $bdtopoConnection,
        private RoadGeocoderInterface $roadGeocoder,
        private readonly RoadSectionMakerInterface $roadSectionMaker,
    ) {
    }

    private function findAdministrator(string $roadNumber): string
    {
        // La colonne 'code_gestionnaire' n'est pas standard, il n'y a pas d'équivalent dans la BDTOPO.
        // Tous les gestionnaires dans le CSV sont des DIR. Et dans la BDTOPO, il n'existe qu'un gestionnaire DIR par nationale.
        // On retrouve donc le gestionnaire BDTOPO à partir du numéro de nationale.

        $row = $this->bdtopoConnection->fetchAssociative(
            'SELECT gestionnaire FROM route_numerotee_ou_nommee WHERE numero = :numero AND gestionnaire LIKE \'DIR%\'',
            ['numero' => $roadNumber],
        );

        return $row['gestionnaire'];
    }

    private function parseRoadNumber(string $value): string
    {
        // Examples:
        // N0001 -> N1
        // N0109 -> N109

        if (!preg_match('/^N0*(?P<number>\d+)$/', $value, $matches)) {
            throw new \RuntimeException(\sprintf('Unexpected id_route: bad format: "%s"', $value));
        }

        return \sprintf('N%s', $matches['number']);
    }

    private function parsePointNumberAndSide(string $value): array
    {
        // Examples:
        // 61PR2D -> ['2', 'D']
        // 10PR24U -> ['24', 'U']

        if (!preg_match('/\d+PR(?P<number>\d+)(?P<side>[DGU])$/', $value, $matches)) {
            throw new \RuntimeException(\sprintf('Unexpected nom_plo: bad format: "%s"', $value));
        }

        return [$matches['number'], $matches['side']];
    }

    public function makeMigrationSql(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $valuesList = [];

        foreach ($rows as $row) {
            if (!str_starts_with($row['id_route'], 'N')) {
                continue;
            }

            // Parfois 'id_route_fin', 'nom_plo_fin' et 'abscisse_fin' ne sont pas remplis, mais on a la 'longueur'.
            // On utilise le même PR/côté et on utilise abscisse + longueur comme abscisse de fin.
            if (!$row['nom_plo_fin'] || !$row['abscisse_fin']) {
                $row['nom_plo_fin'] = $row['nom_plo'];
                $row['abscisse_fin'] = (string) ((int) $row['abscisse'] + (int) $row['longueur']);
            }

            $sourceId = $row['id2'];
            $description = $row['description_infobulle'];
            $roadNumber = $this->parseRoadNumber($row['id_route']);
            $administrator = $this->findAdministrator($roadNumber);
            [$fromPointNumber, $fromSide] = $this->parsePointNumberAndSide($row['nom_plo']);
            $fromAbscissa = (int) $row['abscisse'];
            [$toPointNumber, $toSide] = $this->parsePointNumberAndSide($row['nom_plo_fin']);
            $toAbscissa = (int) $row['abscisse_fin'];

            $fullRoadGeometry = $this->roadGeocoder->computeRoad('Nationale', $administrator, $roadNumber);

            try {
                $geometry = $this->roadSectionMaker->computeSection(
                    $fullRoadGeometry,
                    'Nationale',
                    $administrator,
                    $roadNumber,
                    $fromPointNumber,
                    $fromSide,
                    $fromAbscissa,
                    $toPointNumber,
                    $toSide,
                    $toAbscissa,
                    DirectionEnum::BOTH->value,
                );
            } catch (GeocodingFailureException $exc) {
                // TODO
                continue;
            }

            $values = [
                'uuid' => 'uuid_generate_v4()',
                'source_id' => \sprintf("'%s'", $sourceId),
                'description' => \sprintf("'%s'", $description),
                'administrator' => \sprintf("'%s'", $administrator),
                'road_number' => \sprintf("'%s'", $roadNumber),
                'from_point_number' => \sprintf("'%s'", $fromPointNumber),
                'from_side' => \sprintf("'%s'", $fromSide),
                'from_abscissa' => $fromAbscissa,
                'to_point_number' => \sprintf("'%s'", $toPointNumber),
                'to_side' => \sprintf("'%s'", $toSide),
                'to_abscissa' => $toAbscissa,
                'geometry' => \sprintf("ST_GeomFromGeoJSON('%s')", $geometry),
            ];

            $valuesList[] = \sprintf('(%s)', implode(', ', $values));
        }

        $columns = [
            'uuid',
            'source_id',
            'description',
            'administrator',
            'road_number',
            'from_point_number',
            'from_side',
            'from_abscissa',
            'to_point_number',
            'to_side',
            'to_abscissa',
            'geometry',
        ];

        $updateColumns = array_filter($columns, fn ($col) => !\in_array($col, ['uuid', 'source_id']));

        return \sprintf(
            'INSERT INTO storage_area (%s) VALUES
%s
ON CONFLICT (source_id) DO UPDATE
SET %s;',
            implode(', ', $columns),
            implode(\sprintf(',%s', PHP_EOL), $valuesList),
            implode(', ', array_map(fn ($col) => \sprintf('%s = EXCLUDED.%s', $col, $col), $updateColumns)),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\OrganizationAdministrativeBoundariesGeometryInterface;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrganizationAdministrativeBoundariesGeometry implements OrganizationAdministrativeBoundariesGeometryInterface
{
    public function __construct(
        private HttpClientInterface $geoApiClient,
        private Connection $defaultConnection,
    ) {
    }

    public function findByCodes(string $code, string $codeType): string
    {
        $search = match ($codeType) {
            OrganizationCodeTypeEnum::INSEE->value => \sprintf('communes/%s?fields=contour', $code),
            OrganizationCodeTypeEnum::DEPARTMENT->value => \sprintf('communes?codeDepartement=%s&fields=contour', $code),
            OrganizationCodeTypeEnum::REGION->value => \sprintf('communes?codeRegion=%s&fields=contour', $code),
            OrganizationCodeTypeEnum::EPCI->value => \sprintf('communes?codeEpci=%s&fields=contour', $code),
            default => throw new \LogicException(\sprintf('Code type "%s" not managed', $codeType)),
        };

        $response = $this->geoApiClient->request('GET', $search);
        $results = $response->toArray();

        if (empty($results)) {
            throw new \LogicException(\sprintf('No administrative boundaries found for code "%s" of type "%s"', $code, $codeType));
        }

        // Dans le cas d'une commune, on a un seul contour et on le convertit en tableau
        if ($codeType === OrganizationCodeTypeEnum::INSEE->value) {
            $results = [$results];
        }

        // Récupération des paramètres de simplification en fonction du type de code
        $simplificationFactor = $this->getSimplificationFactor($codeType);
        $validGeometries = $this->processGeometryBatches($results);

        if (empty($validGeometries)) {
            throw new \LogicException('No valid geometries found for administrative boundaries');
        }

        return $this->unifyGeometries($validGeometries, $simplificationFactor);
    }

    private function processGeometryBatches(array $results): array
    {
        $batchSize = 50;
        $totalResults = \count($results);
        $validGeometries = [];

        for ($i = 0; $i < $totalResults; $i += $batchSize) {
            $batch = \array_slice($results, $i, $batchSize);

            foreach ($batch as $result) {
                if (!isset($result['contour']['coordinates']) || empty($result['contour']['coordinates'])) {
                    continue;
                }

                $geoJson = json_encode([
                    'type' => $result['contour']['type'],
                    'coordinates' => $result['contour']['coordinates'],
                ]);

                try {
                    $wkt = $this->defaultConnection->executeQuery('SELECT ST_AsText(ST_GeomFromGeoJSON(?))', [$geoJson])->fetchOne();
                    if ($wkt) {
                        $validGeometries[] = $wkt;
                    }
                } catch (\Exception) {
                    // Ignorer les géométries invalides
                    continue;
                }
            }
        }

        return $validGeometries;
    }

    private function getSimplificationFactor(string $codeType): float
    {
        return match ($codeType) {
            OrganizationCodeTypeEnum::INSEE->value => 0, // Pas de simplification
            OrganizationCodeTypeEnum::DEPARTMENT->value => 0.001, // Simplification moyenne (~110m)
            OrganizationCodeTypeEnum::REGION->value => 0.003, // Simplification forte (~210m)
            OrganizationCodeTypeEnum::EPCI->value => 0.002, // Simplification assez forte (~160m)
            default => 0.001,
        };
    }

    private function unifyGeometries(array $validGeometries, float $simplificationFactor): string
    {
        // Construction de la requête pour unir toutes les géométries
        $geometriesUnion = implode(', ', array_map(function ($wkt) {
            return "ST_GeomFromText('$wkt')";
        }, $validGeometries));

        // Union de toutes les géométries
        $unionQuery = "SELECT ST_AsText(ST_Union(ARRAY[$geometriesUnion]))";
        $unionResult = $this->defaultConnection->executeQuery($unionQuery)->fetchOne();

        if (!$unionResult) {
            throw new \LogicException('Impossible to generate a valid geometry for administrative boundaries');
        }

        // Simplification de la géométrie
        $finalQuery = "SELECT ST_AsGeoJSON(ST_SimplifyPreserveTopology(?, {$simplificationFactor}))";

        $result = $this->defaultConnection->executeQuery($finalQuery, [$unionResult])->fetchOne();

        if (!$result) {
            throw new \LogicException('Impossible to generate a valid geometry for administrative boundaries');
        }

        return $result;
    }
}

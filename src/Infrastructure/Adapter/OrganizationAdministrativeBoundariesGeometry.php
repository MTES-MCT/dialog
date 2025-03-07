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
        $queryParam = match ($codeType) {
            OrganizationCodeTypeEnum::INSEE->value => \sprintf('codePostal=%s', $code),
            OrganizationCodeTypeEnum::DEPARTMENT->value => \sprintf('codeDepartement=%s', $code),
            OrganizationCodeTypeEnum::REGION->value => \sprintf('codeRegion=%s', $code),
            OrganizationCodeTypeEnum::EPCI->value => \sprintf('codeEpci=%s', $code),
            default => throw new \LogicException(\sprintf('Code type "%s" not managed', $codeType)),
        };

        $response = $this->geoApiClient->request('GET', \sprintf('communes?%s&fields=contour', $queryParam));
        $results = $response->toArray();

        if (empty($results)) {
            throw new \LogicException(\sprintf('No administrative boundaries found for code "%s" of type "%s"', $code, $codeType));
        }

        // Traitement par lots pour éviter les problèmes de mémoire avec les grandes métropoles
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

                // Validation de la géométrie
                try {
                    $wkt = $this->defaultConnection->executeQuery('SELECT ST_AsText(ST_GeomFromGeoJSON(?))', [$geoJson])->fetchOne();
                    if ($wkt) {
                        $validGeometries[] = $wkt;
                    }
                } catch (\Exception $e) {
                    // Ignorer les géométries invalides
                    continue;
                }
            }
        }

        if (empty($validGeometries)) {
            throw new \LogicException('No valid geometries found for administrative boundaries');
        }

        // Construction de la requête pour unir toutes les géométries
        $geometriesUnion = implode(', ', array_map(function ($wkt) {
            return "ST_GeomFromText('$wkt')";
        }, $validGeometries));

        // Union de toutes les géométries et simplification pour réduire le nombre de points
        $result = $this->defaultConnection->executeQuery("
            SELECT ST_AsGeoJSON(
                ST_SimplifyPreserveTopology(
                    ST_Union(ARRAY[$geometriesUnion]),
                    0.001
                )
            )
        ")->fetchOne();

        if (!$result) {
            throw new \LogicException('Impossible to generate a valid geometry for administrative boundaries');
        }

        return $result;
    }
}

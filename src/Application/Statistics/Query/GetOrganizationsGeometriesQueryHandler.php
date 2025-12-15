<?php

declare(strict_types=1);

namespace App\Application\Statistics\Query;

use App\Domain\User\Repository\OrganizationRepositoryInterface;

final readonly class GetOrganizationsGeometriesQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetOrganizationsGeometriesQuery $query): array
    {
        $rows = $this->organizationRepository->findAllForStatistics();

        $features = [];

        foreach ($rows as $row) {
            if (empty($row['geometry'])) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geometry'], true),
                'properties' => [
                    // Nom agrégé des organisations du cluster, pour affichage éventuel côté front
                    'clusterName' => $row['cluster_name'],
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}

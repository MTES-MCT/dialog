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
        $organizations = $this->organizationRepository->findAllForStatistics();

        $features = [];

        foreach ($organizations as $org) {
            if (empty($org['geometry'])) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($org['geometry'], true),
                'properties' => [
                    'uuid' => $org['uuid'],
                    'name' => $org['name'],
                    'code' => $org['code'],
                    'codeType' => $org['code_type'],
                    'departmentName' => $org['department_name'],
                    'departmentCode' => $org['department_code'],
                ],
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}

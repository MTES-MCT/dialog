<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RestrictionListItemView;
use App\Domain\Geography\Coordinates;
use App\Domain\Pagination;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class GetRestrictionsQueryHandler
{
    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(GetRestrictionsQuery $query): Pagination
    {
        $result = $this->locationRepository->findAllForRestrictionList($query->dto);

        $items = [];

        foreach ($result['rows'] as $row) {
            $items[] = new RestrictionListItemView(
                regulationOrderRecordUuid: $row['regulation_order_record_uuid'],
                measureType: $row['measure_type'],
                label: 'Rue Hyacinthe Ménagé',
                centroid: Coordinates::fromLonLat((float) $row['centroid_x'], (float) $row['centroid_y']),
            );
        }

        return new Pagination(
            $items,
            $result['totalItems'],
            $query->dto->page,
            $query->dto->pageSize,
        );
    }
}

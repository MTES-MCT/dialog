<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RejectedLocationView;
use App\Domain\Regulation\RejectedLocation;
use App\Domain\Regulation\Repository\RejectedLocationRepositoryInterface;

final class GetRejectedLocationsQueryHandler
{
    public function __construct(
        private readonly RejectedLocationRepositoryInterface $rejectedLocationRepository,
    ) {
    }

    /** @return RejectedLocationView[] */
    public function __invoke(GetRejectedLocationsQuery $query): array
    {
        $rejectedLocations = $query->regulationOrderRecordUuid !== null
            ? $this->rejectedLocationRepository->findByRegulationOrderRecordUuid($query->regulationOrderRecordUuid)
            : $this->rejectedLocationRepository->findByOrganizationUuid($query->organizationUuid);

        return array_map(
            static fn (RejectedLocation $rejectedLocation) => new RejectedLocationView(
                measureIndex: $rejectedLocation->getMeasureIndex(),
                locationIndex: $rejectedLocation->getLocationIndex(),
                location: $rejectedLocation->getLocation(),
                measure: $rejectedLocation->getMeasure(),
                errors: $rejectedLocation->getErrors(),
                regulationOrderIdentifier: $rejectedLocation->getRegulationOrderRecord()->getRegulationOrder()->getIdentifier(),
                createdAt: $rejectedLocation->getCreatedAt(),
            ),
            $rejectedLocations,
        );
    }
}

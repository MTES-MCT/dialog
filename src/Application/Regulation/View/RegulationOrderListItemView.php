<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

final class RegulationOrderListItemView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $status,
        public readonly RegulationOrderRecordSourceEnum $source,
        public readonly int $numLocations,
        public readonly string $organizationName,
        public readonly string $organizationUuid,
        public readonly ?LocationViewInterface $location,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
    ) {
    }

    public function isSourceDialog(): bool
    {
        return $this->source->isDialog();
    }

    public static function fromRow(array $row): self
    {
        $locationView = null;

        if ($row['namedStreet']) {
            [$roadName, $cityLabel, $cityCode] = explode('#', $row['namedStreet']);
            $locationView = new NamedStreetView($cityCode, $cityLabel, $roadName);
        } elseif ($row['numberedRoad']) {
            [$roadNumber, $administrator] = explode('#', $row['numberedRoad']);
            $locationView = new NumberedRoadView($roadNumber, $administrator);
        } elseif ($row['rawGeoJSON']) {
            $label = $row['rawGeoJSON'];
            $locationView = new RawGeoJSONView($label);
        } elseif ($row['wholeCity']) {
            $locationView = new WholeCityView(cityLabel: $row['wholeCity']);
        } elseif ($row['zone']) {
            $locationView = new ZoneView(label: $row['zone']);
        }

        $startDate = $row['overallStartDate'] ? new \DateTimeImmutable($row['overallStartDate']) : null;
        $endDate = null;

        // Returning overallEndDate = NULL for permanent regulation orders is too complex in SQL, we do it here in PHP.
        if ($row['overallEndDate'] && $row['category'] !== RegulationOrderCategoryEnum::PERMANENT_REGULATION->value) {
            $endDate = new \DateTimeImmutable($row['overallEndDate']);
        }

        return new self(
            uuid: $row['uuid'],
            identifier: $row['identifier'],
            status: $row['status'],
            source: RegulationOrderRecordSourceEnum::from($row['source']),
            numLocations: $row['nbLocations'],
            organizationName: $row['organizationName'],
            organizationUuid: $row['organizationUuid'],
            location: $locationView,
            startDate: $startDate,
            endDate: $endDate,
        );
    }
}

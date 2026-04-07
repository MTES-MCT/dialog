<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;

final class RegulationOrderListItemView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $status,
        public readonly string $source,
        public readonly int $numLocations,
        public readonly string $organizationName,
        public readonly string $organizationUuid,
        public readonly ?LocationViewInterface $location,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
    ) {
    }

    public function allowsEditingPublishedRegulationFromList(): bool
    {
        if ($this->status !== RegulationOrderRecordStatusEnum::PUBLISHED->value) {
            return false;
        }

        return !\in_array(
            $this->source,
            [
                RegulationOrderRecordSourceEnum::LITTERALIS->value,
                RegulationOrderRecordSourceEnum::API->value,
            ],
            true,
        );
    }
}

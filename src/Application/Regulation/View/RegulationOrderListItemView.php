<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

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

    public function isSourceDialog(): bool
    {
        return $this->source === RegulationOrderRecordSourceEnum::DIALOG->value;
    }
}

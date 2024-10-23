<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

use App\Domain\Regulation\Enum\MeasureTypeEnum;

final class MapFilterDTO
{
    public bool $displayPermanentRegulations = true;
    public bool $displayTemporaryRegulations = true;
    public array $measureTypes = [
        MeasureTypeEnum::NO_ENTRY->value, MeasureTypeEnum::SPEED_LIMITATION->value,
    ];
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public ?\DateTimeInterface $startDate = null,
    ) {
    }
}

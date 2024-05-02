<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class NamedStreetView implements LocationViewInterface
{
    public string $roadType;

    public function __construct(
        public ?string $cityCode = null,
        public ?string $cityLabel = null,
        public ?string $roadName = null,
    ) {
        $this->roadType = RoadTypeEnum::LANE->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class ZoneView implements LocationViewInterface
{
    public string $roadType;

    public function __construct(
        public ?string $label = null,
    ) {
        $this->roadType = RoadTypeEnum::ZONE->value;
    }
}

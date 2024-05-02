<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class NumberedRoadView implements LocationViewInterface
{
    public string $roadType;

    public function __construct(
        public ?string $roadNumber = null,
        public ?string $administrator = null,
    ) {
        $this->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
    }
}

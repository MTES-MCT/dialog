<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class WholeCityView implements LocationViewInterface
{
    public string $roadType;

    public function __construct(
        public ?string $cityCode = null,
        public ?string $cityLabel = null,
    ) {
        $this->roadType = RoadTypeEnum::WHOLE_CITY->value;
    }
}

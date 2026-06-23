<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Enum\RoadTypeEnum;

final readonly class WholeCityView implements LocationViewInterface
{
    public string $roadType;

    /**
     * @param string[] $exceptions Road names excluded from the restriction
     */
    public function __construct(
        public ?string $cityCode = null,
        public ?string $cityLabel = null,
        public array $exceptions = [],
    ) {
        $this->roadType = RoadTypeEnum::WHOLE_CITY->value;
    }
}

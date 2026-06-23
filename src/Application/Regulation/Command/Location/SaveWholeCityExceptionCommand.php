<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Location\WholeCityException;

final class SaveWholeCityExceptionCommand
{
    public ?string $roadBanId = null;
    public ?string $roadName = null;

    public function __construct(
        public readonly ?WholeCityException $exception = null,
    ) {
        $this->roadBanId = $exception?->getRoadBanId();
        $this->roadName = $exception?->getRoadName();
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Domain\Regulation\Location\Location;

final readonly class GetZoneGeometryQuery implements QueryInterface
{
    public function __construct(
        public SaveZoneCommand $command,
        public ?Location $location = null,
    ) {
    }
}

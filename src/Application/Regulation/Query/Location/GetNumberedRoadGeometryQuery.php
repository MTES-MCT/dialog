<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Location\Location;

final readonly class GetNumberedRoadGeometryQuery implements QueryInterface
{
    public function __construct(
        public SaveNumberedRoadCommand $command,
        public ?Location $location = null,
        public ?string $geometry = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Domain\Regulation\Location\Location;

final readonly class GetWholeCityGeometryQuery implements QueryInterface
{
    public function __construct(
        public SaveWholeCityCommand $command,
        public ?Location $location = null,
        public ?string $geometry = null,
    ) {
    }
}

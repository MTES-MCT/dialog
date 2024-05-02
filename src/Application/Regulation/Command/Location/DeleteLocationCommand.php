<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location\Location;

final class DeleteLocationCommand implements CommandInterface
{
    public function __construct(
        public readonly Location $location,
    ) {
    }
}

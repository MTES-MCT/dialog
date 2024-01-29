<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\LocationNew;

final class DeleteLocationNewCommand implements CommandInterface
{
    public function __construct(
        public readonly LocationNew $locationNew,
    ) {
    }
}

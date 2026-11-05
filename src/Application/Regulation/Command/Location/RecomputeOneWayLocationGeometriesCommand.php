<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;

final class RecomputeOneWayLocationGeometriesCommand implements CommandInterface
{
    public function __construct(
        // En mode « dry run », les géométries sont recalculées et comparées mais rien n'est enregistré.
        public readonly bool $dryRun = false,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;

final class ConvertPolygonsToZonesCommand implements CommandInterface
{
    public function __construct(
        // En mode « dry run », on liste seulement les localisations qui seraient converties.
        public readonly bool $dryRun = false,
    ) {
    }
}

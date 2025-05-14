<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

final readonly class UpdateNamedStreetsWithoutRoadBanIdsCommandResult
{
    public function __construct(
        public int $numNamedStreets,
        public array $updatedUuids,
        public array $exceptions,
    ) {
    }
}

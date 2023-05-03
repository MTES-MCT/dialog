<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?Location $location;
}

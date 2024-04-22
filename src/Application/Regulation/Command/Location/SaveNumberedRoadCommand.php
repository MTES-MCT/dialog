<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Measure;

final class SaveNumberedRoadCommand implements CommandInterface
{
    public ?string $roadType = null;
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $fromPointNumber = null;
    public ?int $fromAbscissa = null;
    public ?string $fromSide = null;
    public ?string $toPointNumber = null;
    public ?int $toAbscissa = null;
    public ?string $toSide = null;
    public ?string $geometry = null;
    public ?Measure $measure;

    public function __construct(
        public readonly ?NumberedRoad $numberedRoad = null,
    ) {
        $this->administrator = $numberedRoad?->getAdministrator();
        $this->roadNumber = $numberedRoad?->getRoadNumber();
        $this->fromPointNumber = $numberedRoad?->getFromPointNumber();
        $this->fromSide = $numberedRoad?->getFromSide();
        $this->fromAbscissa = $numberedRoad?->getFromAbscissa();
        $this->toPointNumber = $numberedRoad?->getToPointNumber();
        $this->toAbscissa = $numberedRoad?->getToAbscissa();
        $this->toSide = $numberedRoad?->getToSide();
    }
}

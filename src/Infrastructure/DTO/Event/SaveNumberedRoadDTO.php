<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Regulation\Enum\DirectionEnum;

final class SaveNumberedRoadDTO
{
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $fromDepartmentCode = null;
    public ?string $fromPointNumber = null;
    public ?int $fromAbscissa = null;
    public ?string $fromSide = null;
    public ?string $toDepartmentCode = null;
    public ?string $toPointNumber = null;
    public ?int $toAbscissa = null;
    public ?string $toSide = null;
    public ?DirectionEnum $direction = null; // DirectionEnum
    public ?string $geometry = null;
    public ?string $storageAreaUuid = null;
}

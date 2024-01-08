<?php

declare(strict_types=1);

namespace App\Domain\Condition;

interface IsMeasureForAllVehiclesInterface
{
    public function getRestrictedTypes(): array;

    public function getMaxWidth(): ?float;

    public function getMaxLength(): ?float;

    public function getMaxHeight(): ?float;
}

<?php

declare(strict_types=1);

namespace App\Domain\EudonetParis;

final class EudonetParisLocationItem
{
    public string $roadName;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $geometry = null;
    /** @var \App\Application\Regulation\Command\SaveMeasureCommand[] */
    public array $measures = [];
}

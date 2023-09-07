<?php

declare(strict_types=1);

namespace App\Domain\EudonetParis;

final class EudonetParisLocationItem
{
    public string $address;
    public ?string $fromHouseNumber = null;
    public ?string $toHouseNumber = null;
    public ?string $fromPoint = null;
    public ?string $toPoint = null;
    /** @var \App\Application\Regulation\Command\SaveMeasureCommand[] */
    public array $measures = [];
}

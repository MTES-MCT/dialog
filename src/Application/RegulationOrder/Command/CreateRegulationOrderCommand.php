<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Command;

use App\Application\CommandInterface;

final class CreateRegulationOrderCommand implements CommandInterface
{
    public string $description;
    public string $issuingAuthority;
    public ?\DateTimeInterface $startPeriod;
    public ?\DateTimeInterface $endPeriod;
}

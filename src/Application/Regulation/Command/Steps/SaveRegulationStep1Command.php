<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;

final class SaveRegulationStep1Command implements CommandInterface
{
    public string $description;
    public string $issuingAuthority;
}

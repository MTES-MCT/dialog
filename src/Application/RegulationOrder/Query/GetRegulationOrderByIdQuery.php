<?php

declare(strict_types=1);

namespace App\Application\RegulationOrder\Query;

use App\Application\QueryInterface;

final class GetRegulationOrderByIdQuery implements QueryInterface
{
    public function __construct(public readonly string $uuid)
    {
    }
}

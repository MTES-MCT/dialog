<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Visa;

use App\Application\QueryInterface;

final class GetVisasAndReasonsByRegulationOrderQuery implements QueryInterface
{
    public function __construct(
        public readonly string $regulationOrderUuid,
    ) {
    }
}

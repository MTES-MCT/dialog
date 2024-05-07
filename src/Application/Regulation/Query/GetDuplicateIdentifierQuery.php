<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;

final readonly class GetDuplicateIdentifierQuery implements QueryInterface
{
    public function __construct(
        public string $identifier,
    ) {
    }
}

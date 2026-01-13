<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\QueryInterface;
use App\Domain\User\Organization;

final readonly class GetRegulationOrderRecordByIdentifierQuery implements QueryInterface
{
    public function __construct(
        public string $identifier,
        public Organization $organization,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Visa\Query;

use App\Application\QueryInterface;

final class GetOrganizationVisaModelsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\VisaModel\Query;

use App\Application\QueryInterface;

final class GetVisaModelsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $organizationUuid = null,
    ) {
    }
}

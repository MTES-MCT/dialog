<?php

declare(strict_types=1);

namespace App\Domain\Regulation\DTO;

final class RegulationListFiltersDTO
{
    public ?string $identifier = null;
    public ?string $organizationUuid = null;
    public ?string $regulationOrderType = null;
    public ?string $status = null;
}
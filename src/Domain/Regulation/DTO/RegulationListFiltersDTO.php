<?php

declare(strict_types=1);

namespace App\Domain\Regulation\DTO;

final class RegulationListFiltersDTO
{
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PAGE_SIZE = 20;

    public int $page = self::DEFAULT_PAGE;
    public int $pageSize = self::DEFAULT_PAGE_SIZE;
    public ?string $identifier = null;
    public ?string $organizationUuid = null;
    public ?string $regulationOrderType = null;
    public ?string $status = null;
}

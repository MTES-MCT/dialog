<?php

declare(strict_types=1);

namespace App\Domain\Regulation\DTO;

use App\Domain\Pagination;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;

final class RegulationListFiltersDTO
{
    public const SORT_IDENTIFIER = 'identifier';
    public const SORT_PERIOD = 'period';
    public const SORT_SOURCE = 'source';
    public const SORT_STATUS = 'status';

    public const SORT_DIR_ASC = 'asc';
    public const SORT_DIR_DESC = 'desc';

    public const ALLOWED_SORTS = [
        self::SORT_IDENTIFIER,
        self::SORT_PERIOD,
        self::SORT_SOURCE,
        self::SORT_STATUS,
    ];

    public const ALLOWED_SORT_DIRS = [
        self::SORT_DIR_ASC,
        self::SORT_DIR_DESC,
    ];

    public int $page = Pagination::DEFAULT_PAGE;
    public int $pageSize = Pagination::DEFAULT_PAGE_SIZE;
    public ?string $identifier = null;
    public ?string $organizationUuid = null;
    public ?string $regulationOrderType = null;
    public ?string $status = null;
    public ?string $sort = null;
    public ?string $sortDir = null;
    public ?AbstractAuthenticatedUser $user = null;
}

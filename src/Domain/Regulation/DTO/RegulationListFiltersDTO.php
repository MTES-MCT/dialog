<?php

declare(strict_types=1);

namespace App\Domain\Regulation\DTO;

use App\Domain\Pagination;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;

final class RegulationListFiltersDTO
{
    public int $page = Pagination::DEFAULT_PAGE;
    public int $pageSize = Pagination::DEFAULT_PAGE_SIZE;
    public ?string $identifier = null;
    public ?string $organizationUuid = null;
    public ?string $regulationOrderType = null;
    public ?string $status = null;
    public ?AbstractAuthenticatedUser $user = null;
}

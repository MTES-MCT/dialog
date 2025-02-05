<?php

declare(strict_types=1);

namespace App\Domain\Regulation\DTO;

use App\Domain\Pagination;
use App\Domain\Regulation\Enum\MeasureTypeEnum;

final class RestrictionListFilterDTO
{
    public int $page = Pagination::DEFAULT_PAGE;
    public int $pageSize = Pagination::DEFAULT_PAGE_SIZE_RESTRICTIONS;
    public bool $isPermanent = true;
    public bool $isTemporary = true;
    public array $measureTypes;
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public ?\DateTimeInterface $startDate = null,
    ) {
        $this->measureTypes = array_map(fn (MeasureTypeEnum $case) => $case->value, MeasureTypeEnum::cases());
    }
}

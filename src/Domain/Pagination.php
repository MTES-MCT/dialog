<?php

declare(strict_types=1);

namespace App\Domain;

final class Pagination
{
    public const MAX_ITEMS_PER_PAGE = 20;
    public readonly int $pageCount;

    public function __construct(
        public readonly array $items,
        public readonly int $totalItems,
    ) {
        $this->pageCount = (int) ceil($totalItems / self::MAX_ITEMS_PER_PAGE);
    }
}

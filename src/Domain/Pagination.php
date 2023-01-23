<?php

declare(strict_types=1);

namespace App\Domain;

final class Pagination
{
    public readonly array $windowPages;
    public readonly int $lastPage;
    public readonly bool $hasFirstPageLandmark;
    public readonly bool $hasLeftTruncature;
    public readonly bool $hasRightTruncature;
    public readonly bool $hasLastPageLandmark;

    public function __construct(
        public readonly array $items,
        public readonly int $totalItems,
        int $currentPage,
        int $pageSize,
    ) {
        $this->lastPage = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

        $numSiblings = 2;
        $firstPage = 1;
        $leftSibling = max($currentPage - $numSiblings, $firstPage);
        $rightSibling = min($currentPage + $numSiblings, $this->lastPage);

        $this->windowPages = range($leftSibling, $rightSibling);
        $this->hasLeftTruncature = $leftSibling >= $firstPage + 2;
        $this->hasRightTruncature = $rightSibling <= $this->lastPage - 2;

        $this->hasFirstPageLandmark = $leftSibling >= $firstPage + 1;
        $this->hasLastPageLandmark = $rightSibling <= $this->lastPage - 1;
    }
}

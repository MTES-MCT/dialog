<?php

declare(strict_types=1);

namespace App\Domain;

final class Pagination
{
    public array $windowPages;
    public int $lastPage;
    public bool $hasFirstPageLandmark;
    public bool $hasLeftTruncature;
    public bool $hasRightTruncature;
    public bool $hasLastPageLandmark;

    public function __construct(
        public array $items,
        public int $totalItems,
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

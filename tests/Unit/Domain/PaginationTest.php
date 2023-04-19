<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testWithTruncature(): void
    {
        $pagination = new Pagination(
            [[], [], [], [], [], [], [], [], [], [], []],
            11,
            5,
            1,
        );

        $this->assertSame(true, $pagination->hasFirstPageLandmark);
        $this->assertSame(true, $pagination->hasLastPageLandmark);
        $this->assertSame(true, $pagination->hasLeftTruncature);
        $this->assertSame(true, $pagination->hasRightTruncature);
        $this->assertSame([[], [], [], [], [], [], [], [], [], [], []], $pagination->items);
        $this->assertSame(11, $pagination->totalItems);
        $this->assertSame([3, 4, 5, 6, 7], $pagination->windowPages);
        $this->assertSame(11, $pagination->lastPage);
    }

    public function testWithoutTruncature(): void
    {
        $pagination = new Pagination(
            [[], [], [], [], [], [], [], [], [], [], []],
            11,
            1,
            10,
        );

        $this->assertSame(false, $pagination->hasFirstPageLandmark);
        $this->assertSame(false, $pagination->hasLastPageLandmark);
        $this->assertSame(false, $pagination->hasLeftTruncature);
        $this->assertSame(false, $pagination->hasRightTruncature);
        $this->assertSame([[], [], [], [], [], [], [], [], [], [], []], $pagination->items);
        $this->assertSame(11, $pagination->totalItems);
        $this->assertSame([1, 2], $pagination->windowPages);
        $this->assertSame(2, $pagination->lastPage);
    }
}

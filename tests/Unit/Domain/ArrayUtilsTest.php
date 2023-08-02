<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\ArrayUtils;
use PHPUnit\Framework\TestCase;

final class ArrayUtilsTest extends TestCase
{
    private ArrayUtils $arrayUtils;

    protected function setUp(): void
    {
        $this->arrayUtils = new ArrayUtils();
    }

    public function testGroupBy(): void
    {
        $this->assertEquals([], $this->arrayUtils->groupBy(fn ($item) => 'never', []));

        $item1 = ['name' => 'A', 'x' => 5, 'y' => 2];
        $item2 = ['name' => 'A', 'x' => 2, 'y' => 5];
        $item3 = ['name' => 'B', 'x' => 3, 'y' => 3];
        $item4 = ['name' => 'C', 'x' => 1, 'y' => 5];

        $items = [$item1, $item2, $item3, $item4];

        $this->assertEquals(['test' => $items], $this->arrayUtils->groupBy(fn ($item) => 'test', $items));

        $this->assertEquals(
            [
                'A' => [$item1, $item2],
                'B' => [$item3],
                'C' => [$item4],
            ],
            $this->arrayUtils->groupBy(fn ($item) => $item['name'], $items),
        );

        $this->assertEquals(
            [
                7 => [$item1, $item2],
                6 => [$item3, $item4],
            ],
            $this->arrayUtils->groupBy(fn ($item) => $item['x'] + $item['y'], $items),
        );
    }
}

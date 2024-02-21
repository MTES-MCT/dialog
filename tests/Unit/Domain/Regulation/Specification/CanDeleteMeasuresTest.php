<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use PHPUnit\Framework\TestCase;

final class CanDeleteMeasuresTest extends TestCase
{
    private $regulationOrderRecord;

    public function setUp(): void
    {
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testCanDeleteMeasures(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countMeasures')
            ->willReturn(2);

        $specification = new CanDeleteMeasures();
        $this->assertTrue($specification->isSatisfiedBy($this->regulationOrderRecord));
    }

    public function testCantDeleteMeasures(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countMeasures')
            ->willReturn(1);

        $specification = new CanDeleteMeasures();
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrderRecord));
    }
}

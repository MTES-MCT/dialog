<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanDeleteLocations;
use PHPUnit\Framework\TestCase;

final class CanDeleteLocationsTest extends TestCase
{
    private $regulationOrderRecord;

    public function setUp(): void
    {
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testCanDeleteLocations(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countLocations')
            ->willReturn(2);

        $specification = new CanDeleteLocations();
        $this->assertTrue($specification->isSatisfiedBy($this->regulationOrderRecord));
    }

    public function testCantDeleteLocations(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countLocations')
            ->willReturn(1);

        $specification = new CanDeleteLocations();
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrderRecord));
    }
}

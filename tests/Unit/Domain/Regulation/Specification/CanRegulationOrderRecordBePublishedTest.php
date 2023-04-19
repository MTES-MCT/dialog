<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationOrderRecordBePublished;
use PHPUnit\Framework\TestCase;

final class CanRegulationOrderRecordBePublishedTest extends TestCase
{
    private $regulationOrderRecord;

    public function setUp(): void
    {
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
    }

    public function testRegulationCanBePublished(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countLocations')
            ->willReturn(1);

        $specification = new CanRegulationOrderRecordBePublished();
        $this->assertTrue($specification->isSatisfiedBy($this->regulationOrderRecord));
    }

    public function testRegulationCannotBePublished(): void
    {
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('countLocations')
            ->willReturn(0);

        $specification = new CanRegulationOrderRecordBePublished();
        $this->assertFalse($specification->isSatisfiedBy($this->regulationOrderRecord));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Specification\CanEditRegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class CanEditRegulationOrderRecordTest extends TestCase
{
    private CanEditRegulationOrderRecord $specification;

    protected function setUp(): void
    {
        $this->specification = new CanEditRegulationOrderRecord();
    }

    public function testAllowsDialog(): void
    {
        $this->assertTrue(
            $this->specification->isSatisfiedBy(RegulationOrderRecordSourceEnum::DIALOG->value),
        );
    }

    public function testBlocksLitteralis(): void
    {
        $this->assertFalse(
            $this->specification->isSatisfiedBy(RegulationOrderRecordSourceEnum::LITTERALIS->value),
        );
    }

    public function testBlocksApi(): void
    {
        $this->assertFalse(
            $this->specification->isSatisfiedBy(RegulationOrderRecordSourceEnum::API->value),
        );
    }
}

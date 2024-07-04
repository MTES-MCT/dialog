<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Specification\CanViewRegulationDetail;
use PHPUnit\Framework\TestCase;

final class CanViewRegulationDetailTest extends TestCase
{
    private $spec;

    public function setUp(): void
    {
        $this->spec = new CanViewRegulationDetail();
    }

    public function testGranted(): void
    {
        $this->assertTrue($this->spec->isSatisfiedBy(
            userId: null,
            status: RegulationOrderRecordStatusEnum::PUBLISHED,
        ));

        $this->assertTrue($this->spec->isSatisfiedBy(
            userId: '066868b3-accf-7f18-8000-7daddb86cc7a',
            status: RegulationOrderRecordStatusEnum::PUBLISHED,
        ));

        $this->assertTrue($this->spec->isSatisfiedBy(
            userId: '066868b3-accf-7f18-8000-7daddb86cc7a',
            status: RegulationOrderRecordStatusEnum::DRAFT,
        ));
    }

    public function testDenied(): void
    {
        $this->assertFalse($this->spec->isSatisfiedBy(
            userId: null,
            status: RegulationOrderRecordStatusEnum::DRAFT,
        ));
    }
}

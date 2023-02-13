<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanDeleteRegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class CanDeleteRegulationOrderRecordTest extends TestCase
{
    public function testCanDelete(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::exactly(2))
            ->method('getUuid')
            ->willReturn('f331d768-ed8b-496d-81ce-b97008f338d0');

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $specification = new CanDeleteRegulationOrderRecord();
        $this->assertTrue($specification->isSatisfiedBy($organization, $regulationOrderRecord));
    }

    public function testCannotDelete(): void
    {

        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f331d768-ed8b-496d-81ce-b97008f338d0');

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $otherOrganization = $this->createMock(Organization::class);
        $otherOrganization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('d9621f56-d0b6-4cfc-b934-658ec0c15878');

        $specification = new CanDeleteRegulationOrderRecord();
        $this->assertFalse($specification->isSatisfiedBy($otherOrganization, $regulationOrderRecord));
    }
}

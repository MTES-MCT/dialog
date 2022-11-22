<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder;

use App\Domain\RegulationOrder\Enum\RegulationOrderRecordStatus;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RegulationOrderRecordTest extends TestCase
{
    public function testGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2022-11-22');
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = new RegulationOrderRecord(
            '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            RegulationOrderRecordStatus::PUBLISHED,
            $organization,
            $regulationOrder,
            $createdAt
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrderRecord->getUuid());
        $this->assertSame($regulationOrder, $regulationOrderRecord->getRegulationOrder());
        $this->assertSame($organization, $regulationOrderRecord->getOrganization());
        $this->assertSame($createdAt, $regulationOrderRecord->getCreatedAt());
    }
}

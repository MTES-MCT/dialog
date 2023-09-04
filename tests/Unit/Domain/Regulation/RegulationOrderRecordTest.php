<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RegulationOrderRecordTest extends TestCase
{
    public function testGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2022-11-22');
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('4cea911e-edbc-49f1-a855-7a38d21e1209');
        $organization
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Dialog');
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = new RegulationOrderRecord(
            '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $regulationOrder,
            $createdAt,
            $organization,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrderRecord->getUuid());
        $this->assertSame($regulationOrder, $regulationOrderRecord->getRegulationOrder());
        $this->assertSame($organization, $regulationOrderRecord->getOrganization());
        $this->assertSame('4cea911e-edbc-49f1-a855-7a38d21e1209', $regulationOrderRecord->getOrganizationUuid());
        $this->assertSame('Dialog', $regulationOrderRecord->getOrganizationName());
        $this->assertSame(RegulationOrderRecordSourceEnum::DIALOG->value, $regulationOrderRecord->getSource());
        $this->assertSame($createdAt, $regulationOrderRecord->getCreatedAt());
        $this->assertSame(RegulationOrderRecordStatusEnum::PUBLISHED, $regulationOrderRecord->getStatus());
        $this->assertFalse($regulationOrderRecord->isDraft());

        $regulationOrderRecord->updateStatus(RegulationOrderRecordStatusEnum::DRAFT);
        $this->assertSame(RegulationOrderRecordStatusEnum::DRAFT, $regulationOrderRecord->getStatus());
        $this->assertTrue($regulationOrderRecord->isDraft());
    }
}

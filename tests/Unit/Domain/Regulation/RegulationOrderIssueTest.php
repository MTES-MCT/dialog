<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderIssueLevelEnum;
use App\Domain\Regulation\RegulationOrderIssue;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RegulationOrderIssueTest extends TestCase
{
    public function testGetters(): void
    {
        $createdAt = new \DateTimeImmutable('2022-11-22');
        $organization = $this->createMock(Organization::class);

        $regulationOrderIssue = new RegulationOrderIssue(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            level: RegulationOrderIssueLevelEnum::WARNING->value,
            organization: $organization,
            source: 'eudonet',
            identifier: 'FO1/2O23',
            context: 'Failed to parse measure dates',
            createdAt: $createdAt,
            geometry: null,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrderIssue->getUuid());
        $this->assertSame(RegulationOrderIssueLevelEnum::WARNING->value, $regulationOrderIssue->getLevel());
        $this->assertSame($organization, $regulationOrderIssue->getOrganization());
        $this->assertSame('eudonet', $regulationOrderIssue->getSource());
        $this->assertSame('FO1/2O23', $regulationOrderIssue->getIdentifier());
        $this->assertSame('Failed to parse measure dates', $regulationOrderIssue->getContext());
        $this->assertSame($createdAt, $regulationOrderIssue->getCreatedAt());
        $this->assertNull($regulationOrderIssue->getGeometry());
    }
}

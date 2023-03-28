<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\Specification\DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;
use PHPUnit\Framework\TestCase;

final class DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifierTest extends TestCase
{
    public function testOrganizationAlreadyHasRegulationOrder(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByOrganizationAndIdentifier')
            ->with($organization, 'FO1/2023')
            ->willReturn($regulationOrderRecord);

        $specification = new DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier($regulationOrderRecordRepository);
        $this->assertTrue($specification->isSatisfiedBy('FO1/2023', $organization));
    }

    public function testOrganizationDoesNotHaveRegulationOrder(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByOrganizationAndIdentifier')
            ->with($organization, 'FO1/2023')
            ->willReturn(null);

        $specification = new DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier($regulationOrderRecordRepository);
        $this->assertFalse($specification->isSatisfiedBy('FO1/2023', $organization));
    }
}

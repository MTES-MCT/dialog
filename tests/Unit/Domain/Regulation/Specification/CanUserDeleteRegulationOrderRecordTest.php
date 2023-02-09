<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Specification;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanUserDeleteRegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class CanUserDeleteRegulationOrderRecordTest extends TestCase
{
    private $organizationUuid = 'f331d768-ed8b-496d-81ce-b97008f338d0';
    private $organization;
    private $otherOrganizationUuid = 'd9621f56-d0b6-4cfc-b934-658ec0c15878';
    private $regulationOrderRecord;

    public function setUp(): void
    {
        $this->organization = $this->createMock(Organization::class);
        $this->organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($this->organizationUuid);

        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->organization);
    }

    private function provideCanDelete(): array
    {
        return [
            [[$this->organizationUuid]],
            [[$this->organizationUuid, $this->otherOrganizationUuid]],
            [[$this->otherOrganizationUuid, $this->organizationUuid]], // Order does not matter
            [[$this->organizationUuid, $this->organizationUuid, $this->otherOrganizationUuid]], // Duplication is OK
        ];
    }


    /**
     * @dataProvider provideCanDelete
     */
    public function testCanDelete(array $userOrganizationUuids): void
    {
        $specification = new CanUserDeleteRegulationOrderRecord();
        $this->assertTrue($specification->isSatisfiedBy($userOrganizationUuids, $this->regulationOrderRecord));
    }

    private function provideCannotDelete(): array
    {
        return [
            [[$this->otherOrganizationUuid]],
            [[]],
        ];
    }

    /**
     * @dataProvider provideCannotDelete
     */
    public function testCannotDelete(array $userOrganizationUuids): void
    {
        $specification = new CanUserDeleteRegulationOrderRecord();
        $this->assertFalse($specification->isSatisfiedBy($userOrganizationUuids, $this->regulationOrderRecord));
    }
}

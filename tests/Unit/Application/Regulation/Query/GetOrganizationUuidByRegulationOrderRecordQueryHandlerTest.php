<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetOrganizationUuidByRegulationOrderRecordQuery;
use App\Application\Regulation\Query\GetOrganizationUuidByRegulationOrderRecordQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use PHPUnit\Framework\TestCase;

final class GetOrganizationUuidByRegulationOrderRecordQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $organizationUuid = '0667c1d0-0e7f-7e95-8000-92a5228804d7';
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($organizationUuid);

        $handler = new GetOrganizationUuidByRegulationOrderRecordQueryHandler($regulationOrderRecordRepository);
        $result = $handler(new GetOrganizationUuidByRegulationOrderRecordQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($organizationUuid, $result);
    }

    public function testNoResult(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetOrganizationUuidByRegulationOrderRecordQueryHandler($regulationOrderRecordRepository);
        $handler(new GetOrganizationUuidByRegulationOrderRecordQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}

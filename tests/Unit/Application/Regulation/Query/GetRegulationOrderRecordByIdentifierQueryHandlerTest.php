<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordByIdentifierQueryHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderRecordByIdentifierQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByIdentifierInOrganization')
            ->with('FO1/2023', $organization)
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordByIdentifierQueryHandler($regulationOrderRecordRepository);
        $result = $handler(new GetRegulationOrderRecordByIdentifierQuery('FO1/2023', $organization));

        $this->assertEquals($regulationOrderRecord, $result);
    }

    public function testNoRegulationOrderRecord(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $organization = $this->createMock(Organization::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByIdentifierInOrganization')
            ->with('FO1/2023', $organization)
            ->willReturn(null);

        $handler = new GetRegulationOrderRecordByIdentifierQueryHandler($regulationOrderRecordRepository);
        $handler(new GetRegulationOrderRecordByIdentifierQuery('FO1/2023', $organization));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQueryHandler;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderRecordByUuidQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($regulationOrderRecord);

        $handler = new GetRegulationOrderRecordByUuidQueryHandler($regulationOrderRecordRepository);
        $result = $handler(new GetRegulationOrderRecordByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($regulationOrderRecord, $result);
    }

    public function testNoRegulationOrderRecord(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetRegulationOrderRecordByUuidQueryHandler($regulationOrderRecordRepository);
        $handler(new GetRegulationOrderRecordByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}

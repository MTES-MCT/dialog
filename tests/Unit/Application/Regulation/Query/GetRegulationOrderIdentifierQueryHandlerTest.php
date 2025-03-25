<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQuery;
use App\Application\Regulation\Query\GetRegulationOrderIdentifierQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderIdentifierQueryHandlerTest extends TestCase
{
    public function testGetRegulationOrderIdentifier(): void
    {
        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $organizationUuid = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';
        $mockDate = new \DateTimeImmutable('2022-01-20 08:41:57');

        $repository
            ->expects(self::once())
            ->method('countRegulationOrderRecordsForOrganizationDuringCurrentMonth')
            ->with($organizationUuid)
            ->willReturn(11)
        ;

        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($mockDate)
        ;

        $identifier = '2022-01-0011';

        $handler = new GetRegulationOrderIdentifierQueryHandler($repository, $dateUtils);
        $result = $handler(new GetRegulationOrderIdentifierQuery($organizationUuid));

        $this->assertEquals($identifier, $result);
    }
}

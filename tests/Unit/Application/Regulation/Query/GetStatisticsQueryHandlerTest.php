<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetStatisticsQuery;
use App\Application\Regulation\Query\GetStatisticsQueryHandler;
use App\Application\Regulation\View\StatisticsView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetStatisticsQueryHandlerTest extends TestCase
{
    public function testStatistics(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $userRepository
            ->expects(self::once())
            ->method('countUsers')
            ->willReturn(10);
        $organizationRepository
            ->expects(self::once())
            ->method('countOrganizations')
            ->willReturn(3);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countTotalRegulationOrderRecords')
            ->willReturn(20);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countPublishedRegulationOrderRecords')
            ->willReturn(10);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countPermanentRegulationOrderRecords')
            ->willReturn(2);
        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countTemporaryRegulationOrderRecords')
            ->willReturn(8);

        $handler = new GetStatisticsQueryHandler($userRepository, $organizationRepository, $regulationOrderRecordRepository);
        $stats = $handler(new GetStatisticsQuery());

        $result = new StatisticsView(
            users: 10,
            organizations: 3,
            totalRegulationOrderRecords: 20,
            publishedRegulationOrderRecords: 10,
            permanentRegulationOrderRecords: 2,
            temporaryRegulationOrderRecords: 8,
        );

        $this->assertEquals($result, $stats);
    }
}

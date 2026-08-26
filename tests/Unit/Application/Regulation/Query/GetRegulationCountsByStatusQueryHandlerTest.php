<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\Query\GetRegulationCountsByStatusQuery;
use App\Application\Regulation\Query\GetRegulationCountsByStatusQueryHandler;
use App\Application\Regulation\View\RegulationCountsByStatusView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationCountsByStatusQueryHandlerTest extends TestCase
{
    public function testGetCounts(): void
    {
        $organizationUuids = ['dcab837f-4460-4355-99d5-bf4891c35f8f'];
        $now = new \DateTimeImmutable('2023-03-15');

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('countRegulationsByStatusForOrganizations')
            ->with($organizationUuids, $now)
            ->willReturn(['draftCount' => 2, 'currentCount' => 3, 'upcomingCount' => 1]);

        $handler = new GetRegulationCountsByStatusQueryHandler($repository, $dateUtils);
        $counts = $handler(new GetRegulationCountsByStatusQuery($organizationUuids));

        $this->assertEquals(new RegulationCountsByStatusView(draftCount: 2, currentCount: 3, upcomingCount: 1), $counts);
    }
}

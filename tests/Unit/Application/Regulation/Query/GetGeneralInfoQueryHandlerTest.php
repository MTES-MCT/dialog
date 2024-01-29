<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetGeneralInfoQueryHandler;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetGeneralInfoQueryHandlerTest extends TestCase
{
    public function testGetGeneralInformation(): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');
        $generalInfo = new GeneralInfoView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            identifier: 'FO1/2024',
            organizationName: 'DiaLog',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: 'draft',
            category: 'other',
            otherCategoryText: 'Other category 1',
            description: 'Description 1',
            startDate: $startDate,
            endDate: $endDate,
        );

        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findGeneralInformation')
            ->willReturn([$generalInfo]);

        $handler = new GetGeneralInfoQueryHandler($repository);

        $this->assertEquals(
            $generalInfo,
            $handler(new GetGeneralInfoQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')),
        );
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderRecordNotFoundException::class);

        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findGeneralInformation')
            ->willReturn([]);

        $handler = new GetGeneralInfoQueryHandler($repository);
        $handler(new GetGeneralInfoQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}

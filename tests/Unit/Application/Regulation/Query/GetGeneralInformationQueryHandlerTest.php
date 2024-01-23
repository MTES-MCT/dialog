<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetGeneralInformationQuery;
use App\Application\Regulation\Query\GetGeneralInformationQueryHandler;
use App\Application\Regulation\View\GeneralInformationView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetGeneralInformationQueryHandlerTest extends TestCase
{
    public function testGetGeneralInformation(): void
    {
        $startDate = new \DateTime('2022-12-07');
        $endDate = new \DateTime('2022-12-17');
        $generalInformation = new GeneralInformationView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
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
            ->willReturn([$generalInformation]);

        $handler = new GetGeneralInformationQueryHandler($repository);

        $this->assertEquals(
            $generalInformation,
            $handler(new GetGeneralInformationQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')),
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

        $handler = new GetGeneralInformationQueryHandler($repository);
        $handler(new GetGeneralInformationQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}

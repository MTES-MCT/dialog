<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetGeneralInfoQuery;
use App\Application\Regulation\Query\GetGeneralInfoQueryHandler;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetGeneralInfoQueryHandlerTest extends TestCase
{
    public function testGetGeneralInformation(): void
    {
        $startDate = '2022-12-07';
        $endDate = '2022-12-17';

        $generalInfo = new GeneralInfoView(
            uuid: '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            identifier: 'FO1/2024',
            organizationName: 'DiaLog',
            organizationLogo: '/path/to/logo.jpg',
            organizationUuid: 'a8439603-40f7-4b1e-8a35-cee9e53b98d4',
            status: RegulationOrderRecordStatusEnum::DRAFT->value,
            regulationOrderUuid: 'fce8177b-3737-4b4e-933d-fe29d0092c89',
            category: 'temporaryRegulation',
            subject: 'other',
            otherCategoryText: 'Other category 1',
            title: 'title 1',
            startDate: new \DateTimeImmutable($startDate),
            endDate: new \DateTimeImmutable($endDate),
        );

        $repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findGeneralInformation')
            ->willReturn([
                'uuid' => $generalInfo->uuid,
                'identifier' => $generalInfo->identifier,
                'organizationName' => $generalInfo->organizationName,
                'organizationUuid' => $generalInfo->organizationUuid,
                'organizationLogo' => '/path/to/logo.jpg',
                'status' => $generalInfo->status,
                'regulationOrderUuid' => 'fce8177b-3737-4b4e-933d-fe29d0092c89',
                'category' => $generalInfo->category,
                'subject' => $generalInfo->subject,
                'otherCategoryText' => $generalInfo->otherCategoryText,
                'title' => $generalInfo->title,
                'overallStartDate' => $startDate,
                'overallEndDate' => $endDate,
            ]);

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

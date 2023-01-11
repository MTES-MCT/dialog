<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQueryHandler;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderListForDatexFormatView;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersToDatexFormatQueryHandlerTest extends TestCase
{
    public function testGetAll(): void
    {
        $startPeriod1 = new \DateTime('2022-12-07');
        $endPeriod1 = new \DateTime('2022-12-17');
        $startPeriod2 = new \DateTime('2022-12-10');

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrder1 = [
            'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
            'description' => 'Description 1',
            'issuingAuthority' => 'Autorité 1',
            'startPeriod' => $startPeriod1,
            'endPeriod' => $endPeriod1,
        ];
        $regulationOrder2 = [
            'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
            'description' => 'Description 2',
            'issuingAuthority' => 'Autorité 2',
            'startPeriod' => $startPeriod2,
            'endPeriod' => null,
        ];

        $regulationOrderRepository
            ->expects(self::once())
            ->method("findRegulationOrdersForDatexFormat")
            ->willReturn([$regulationOrder1, $regulationOrder2]);

        $handler = new GetRegulationOrdersToDatexFormatQueryHandler($regulationOrderRepository);
        $regulationOrders = $handler(new GetRegulationOrdersToDatexFormatQuery());

        $this->assertEquals(
            [
                new RegulationOrderListForDatexFormatView(
                    '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                    'Description 1',
                    'Autorité 1',
                    new PeriodView($startPeriod1, $endPeriod1),
                ),
                new RegulationOrderListForDatexFormatView(
                    '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    'Description 2',
                    'Autorité 2',
                    new PeriodView($startPeriod2),
                ),
            ],
            $regulationOrders,
        );
    }
}

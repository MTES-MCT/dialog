<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Visa;

use App\Application\Regulation\Query\Visa\GetVisasAndReasonsByRegulationOrderQuery;
use App\Application\Regulation\Query\Visa\GetVisasAndReasonsByRegulationOrderQueryHandler;
use App\Application\Regulation\View\VisasAndReasonsView;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetVisasAndReasonsByRegulationOrderQueryHandlerTest extends TestCase
{
    public function testGetVisasAndReasons(): void
    {
        $visasAndReasons = new VisasAndReasonsView(
            ['vu 1', 'vu 2', 'vu 3'],
            ['considérant 1', 'considérant 2'],
        );

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrderRepository
            ->expects(self::once())
            ->method('findVisasAndReasonsByRegulationOrderUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([
                'visas' => ['vu 1'],
                'additionalVisas' => ['vu 2', 'vu 3'],
                'additionalReasons' => ['considérant 1', 'considérant 2'],
            ]);

        $handler = new GetVisasAndReasonsByRegulationOrderQueryHandler($regulationOrderRepository);
        $result = $handler(new GetVisasAndReasonsByRegulationOrderQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($visasAndReasons, $result);
    }

    public function testGetEmptyVisasAndReasons(): void
    {
        $visasAndReasons = new VisasAndReasonsView();

        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationOrderRepository
            ->expects(self::once())
            ->method('findVisasAndReasonsByRegulationOrderUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([]);

        $handler = new GetVisasAndReasonsByRegulationOrderQueryHandler($regulationOrderRepository);
        $result = $handler(new GetVisasAndReasonsByRegulationOrderQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($visasAndReasons, $result);
    }
}

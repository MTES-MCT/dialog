<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForApiQuery;
use App\Application\Regulation\Query\GetRegulationOrdersForApiQueryHandler;
use App\Application\Regulation\View\RegulationOrderForApiView;
use App\Domain\Condition\VehicleSet;
use App\Domain\Pagination;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersForApiQueryHandlerTest extends TestCase
{
    private RegulationOrderRecordRepositoryInterface&MockObject $repository;
    private DateUtilsInterface&MockObject $dateUtils;
    private GetRegulationOrdersForApiQueryHandler $handler;
    private Organization $organization;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->dateUtils->method('getNow')->willReturn(new \DateTimeImmutable('2025-01-01'));
        $this->handler = new GetRegulationOrdersForApiQueryHandler($this->repository, $this->dateUtils);
        $this->organization = $this->createMock(Organization::class);
    }

    private function makeRecord(string $uuid, string $identifier, ?array $restrictedTypes = null): RegulationOrderRecord
    {
        $regulationOrder = new RegulationOrder($uuid . '-ro', $identifier, 'temporaryRegulation', 'Title');

        if ($restrictedTypes !== null) {
            $measure = new Measure($uuid . '-m', $regulationOrder, 'noEntry', new \DateTimeImmutable());
            $measure->setVehicleSet(new VehicleSet($uuid . '-v', $measure, $restrictedTypes));
            $regulationOrder->addMeasure($measure);
        }

        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('org-uuid');
        $organization->method('getName')->willReturn('Org');

        return new RegulationOrderRecord($uuid, 'dialog', 'published', $regulationOrder, new \DateTimeImmutable(), $organization);
    }

    public function testReturnsEmptyPaginationWhenNoUuids(): void
    {
        $this->repository->method('findUuidsForApi')->willReturn([]);
        $this->repository->expects(self::never())->method('iterateRegulationOrdersForApiByUuids');

        $result = $this->handler->__invoke(new GetRegulationOrdersForApiQuery($this->organization));

        $this->assertInstanceOf(Pagination::class, $result);
        $this->assertSame(0, $result->totalItems);
        $this->assertSame([], $result->items);
    }

    public function testBuildsViewsWithOverallDates(): void
    {
        $record = $this->makeRecord('uuid-1', 'F/1', []);

        $this->repository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->repository->method('getOverallDatesByRegulationUuids')->willReturn([
            'uuid-1' => [
                'overallStartDate' => new \DateTimeImmutable('2025-01-01'),
                'overallEndDate' => new \DateTimeImmutable('2025-02-01'),
            ],
        ]);
        $this->repository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);

        $result = $this->handler->__invoke(new GetRegulationOrdersForApiQuery($this->organization));

        $this->assertSame(1, $result->totalItems);
        /** @var RegulationOrderForApiView $view */
        $view = $result->items[0];
        $this->assertInstanceOf(RegulationOrderForApiView::class, $view);
        $this->assertSame('F/1', $view->identifier);
        $this->assertEquals(new \DateTimeImmutable('2025-01-01'), $view->startDate);
        $this->assertEquals(new \DateTimeImmutable('2025-02-01'), $view->endDate);
    }

    public function testExcludesHeavyGoodsVehicleWhenFilterIsFalse(): void
    {
        $withHgv = $this->makeRecord('uuid-hgv', 'F/HGV', [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value]);
        $withoutHgv = $this->makeRecord('uuid-other', 'F/OTHER', [VehicleTypeEnum::BICYCLE->value]);

        $this->repository->method('findUuidsForApi')->willReturn(['uuid-hgv', 'uuid-other']);
        $this->repository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->repository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$withHgv, $withoutHgv]);

        $result = $this->handler->__invoke(new GetRegulationOrdersForApiQuery($this->organization, includeHeavyGoodsVehicle: false));

        $this->assertSame(1, $result->totalItems);
        $this->assertSame('F/OTHER', $result->items[0]->identifier);
    }

    public function testIncludesHeavyGoodsVehicleByDefault(): void
    {
        $withHgv = $this->makeRecord('uuid-hgv', 'F/HGV', [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value]);

        $this->repository->method('findUuidsForApi')->willReturn(['uuid-hgv']);
        $this->repository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->repository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$withHgv]);

        $result = $this->handler->__invoke(new GetRegulationOrdersForApiQuery($this->organization));

        $this->assertSame(1, $result->totalItems);
    }

    public function testPaginatesResults(): void
    {
        $records = [
            $this->makeRecord('u1', 'F/1', []),
            $this->makeRecord('u2', 'F/2', []),
            $this->makeRecord('u3', 'F/3', []),
        ];

        $this->repository->method('findUuidsForApi')->willReturn(['u1', 'u2', 'u3']);
        $this->repository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->repository->method('iterateRegulationOrdersForApiByUuids')->willReturn($records);

        $result = $this->handler->__invoke(new GetRegulationOrdersForApiQuery($this->organization, page: 2, pageSize: 2));

        $this->assertSame(3, $result->totalItems);
        $this->assertCount(1, $result->items);
        $this->assertSame('F/3', $result->items[0]->identifier);
    }
}

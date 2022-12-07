<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommandHandler;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateRegulationOrderCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $startPeriod = new \DateTime('2022-12-07 15:00');
        $endPeriod = new \DateTime('2022-12-17 16:00');

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationConditionRepository = $this->createMock(RegulationConditionRepositoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $overallPeriodRepository = $this->createMock(OverallPeriodRepositoryInterface::class);

        $regulationCondition = new RegulationCondition(
            uuid: 'f331d768-ed8b-496d-81ce-b97008f338d0',
            negate: false,
        );
        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                'f331d768-ed8b-496d-81ce-b97008f338d0',
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                '98d3d3c6-83cd-49ab-b94a-4d4373a31114',
            );

        $regulationConditionRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->equalTo($regulationCondition))
            ->willReturn($regulationCondition);

        $regulationOrderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        description: 'Interdiction de circuler',
                        issuingAuthority: 'Ville de Paris',
                        regulationCondition: $regulationCondition,
                    )
                )
            )
            ->willReturn($createdRegulationOrder);

        $overallPeriodRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new OverallPeriod(
                        uuid: '98d3d3c6-83cd-49ab-b94a-4d4373a31114',
                        regulationCondition: $regulationCondition,
                        startPeriod: $startPeriod,
                        endPeriod: $endPeriod,
                    )
                )
            );

        $createdRegulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('fd035fec0-30f3-4134-95b9-d74c68eb53e3');

        $handler = new CreateRegulationOrderCommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
            $overallPeriodRepository,
        );

        $command = new CreateRegulationOrderCommand();
        $command->description = 'Interdiction de circuler';
        $command->issuingAuthority = 'Ville de Paris';
        $command->startPeriod = $startPeriod;
        $command->endPeriod = $endPeriod;

        $uuid = $handler($command);

        $this->assertSame('fd035fec0-30f3-4134-95b9-d74c68eb53e3', $uuid);
    }
}

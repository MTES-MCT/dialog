<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommandHandler;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateRegulationOrderCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationConditionRepository = $this->createMock(RegulationConditionRepositoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $regulationCondition = new RegulationCondition(
            uuid: 'f331d768-ed8b-496d-81ce-b97008f338d0',
            negate: false,
        );
        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                'f331d768-ed8b-496d-81ce-b97008f338d0',
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
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

        $createdRegulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('fd035fec0-30f3-4134-95b9-d74c68eb53e3');

        $handler = new CreateRegulationOrderCommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
        );

        $command = new CreateRegulationOrderCommand();
        $command->description = 'Interdiction de circuler';
        $command->issuingAuthority = 'Ville de Paris';

        $uuid = $handler($command);

        $this->assertSame('fd035fec0-30f3-4134-95b9-d74c68eb53e3', $uuid);
    }
}

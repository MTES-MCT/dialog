<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder\Command;

use App\Application\IdFactoryInterface;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommandHandler;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateRegulationOrderCommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $idFactory->expects(self::once())->method('make')->willReturn('f331d768-ed8b-496d-81ce-b97008f338d0');

        $regulationOrderRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->equalTo(new RegulationOrder('f331d768-ed8b-496d-81ce-b97008f338d0', 'Interdiction de circuler', 'Ville de Paris')))
            ->willReturn($createdRegulationOrder);

        $createdRegulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f331d768-ed8b-496d-81ce-b97008f338d0');

        $handler = new CreateRegulationOrderCommandHandler($idFactory, $regulationOrderRepository);

        $command = new CreateRegulationOrderCommand();
        $command->description = 'Interdiction de circuler';
        $command->issuingAuthority = 'Ville de Paris';

        $uuid = $handler($command);

        $this->assertSame('f331d768-ed8b-496d-81ce-b97008f338d0', $uuid);
    }
}

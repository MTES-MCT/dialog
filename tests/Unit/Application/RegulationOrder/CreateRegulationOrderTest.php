<?php

declare(strict_types=1);

namespace App\Tests\Domain\RegulationOrder;

use App\Application\IdFactoryInterface;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommand;
use App\Application\RegulationOrder\Command\CreateRegulationOrderCommandHandler;
use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateRegulationOrderTest extends KernelTestCase
{
    public function testCreate(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $validator = $container->get(ValidatorInterface::class);
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

        $handler = new CreateRegulationOrderCommandHandler($idFactory, $validator, $regulationOrderRepository);

        $uuid = $handler(
            new CreateRegulationOrderCommand(
                'Interdiction de circuler',
                'Ville de Paris',
            )
        );

        $this->assertSame('f331d768-ed8b-496d-81ce-b97008f338d0', $uuid);
    }

    public function testCreateInvalid(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $idFactory = $container->get(IdFactoryInterface::class);
        $validator = $container->get(ValidatorInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderRepository->expects(self::never())->method('save');

        $handler = new CreateRegulationOrderCommandHandler($idFactory, $validator, $regulationOrderRepository);

        $this->expectException('Symfony\Component\Validator\Exception\ValidationFailedException');

        $handler(
            new CreateRegulationOrderCommand(
                '',
                '',
            )
        );

    }
}

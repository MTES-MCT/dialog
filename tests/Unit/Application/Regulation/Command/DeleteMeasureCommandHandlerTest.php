<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteMeasureCommand;
use App\Application\Regulation\Command\DeleteMeasureCommandHandler;
use App\Application\Regulation\Command\MapImage\WarmRegulationMapImageCacheCommand;
use App\Domain\Regulation\Exception\MeasureCannotBeDeletedException;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteMeasures;
use PHPUnit\Framework\TestCase;

final class DeleteMeasureCommandHandlerTest extends TestCase
{
    private $measure;
    private $measureRepository;
    private $canDeleteMeasures;
    private $regulationOrderRecord;
    private $commandBus;

    protected function setUp(): void
    {
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getRegulationOrderRecord')
            ->willReturn($this->regulationOrderRecord);

        $this->measure = $this->createMock(Measure::class);
        $this->measure
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $this->measureRepository = $this->createMock(MeasureRepositoryInterface::class);
        $this->canDeleteMeasures = $this->createMock(CanDeleteMeasures::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testDelete(): void
    {
        $this->measureRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->measure));

        $this->canDeleteMeasures
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(true);

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('record-uuid');

        $this->commandBus
            ->expects(self::once())
            ->method('dispatchAsync')
            ->with($this->equalTo(new WarmRegulationMapImageCacheCommand('record-uuid')));

        $handler = new DeleteMeasureCommandHandler($this->measureRepository, $this->canDeleteMeasures, $this->commandBus);

        $command = new DeleteMeasureCommand($this->measure);
        $this->assertEmpty($handler($command));
    }

    public function testCannotDelete(): void
    {
        $this->expectException(MeasureCannotBeDeletedException::class);

        $this->measureRepository
            ->expects(self::never())
            ->method('delete');

        $this->canDeleteMeasures
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(false);

        $this->commandBus
            ->expects(self::never())
            ->method('dispatchAsync');

        $handler = new DeleteMeasureCommandHandler($this->measureRepository, $this->canDeleteMeasures, $this->commandBus);

        $command = new DeleteMeasureCommand($this->measure);
        $this->assertEmpty($handler($command));
    }
}

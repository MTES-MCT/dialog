<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\DeleteMeasureCommand;
use App\Application\Regulation\Command\DeleteMeasureCommandHandler;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteMeasureCommandHandlerTest extends TestCase
{
    private $measure;
    private $measureRepository;

    protected function setUp(): void
    {
        $this->measure = $this->createMock(Measure::class);
        $this->measureRepository = $this->createMock(MeasureRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->measureRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->measure));

        $handler = new DeleteMeasureCommandHandler($this->measureRepository);

        $command = new DeleteMeasureCommand($this->measure);
        $this->assertEmpty($handler($command));
    }
}

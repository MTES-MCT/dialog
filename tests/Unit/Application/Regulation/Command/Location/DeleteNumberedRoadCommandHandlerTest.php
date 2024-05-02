<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteNumberedRoadCommand;
use App\Application\Regulation\Command\Location\DeleteNumberedRoadCommandHandler;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteNumberedRoadCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoadRepository = $this->createMock(NumberedRoadRepositoryInterface::class);
        $numberedRoadRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($numberedRoad));

        $handler = new DeleteNumberedRoadCommandHandler($numberedRoadRepository);
        $command = new DeleteNumberedRoadCommand($numberedRoad);
        $this->assertEmpty($handler($command));
    }
}

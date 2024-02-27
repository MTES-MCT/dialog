<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteLocationCommand;
use App\Application\Regulation\Command\Location\DeleteLocationCommandHandler;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteLocationCommandHandlerTest extends TestCase
{
    private $location;
    private $locationRepository;

    protected function setUp(): void
    {
        $this->location = $this->createMock(Location::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->locationRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->location));

        $handler = new DeleteLocationCommandHandler($this->locationRepository);

        $command = new DeleteLocationCommand($this->location);
        $this->assertEmpty($handler($command));
    }
}

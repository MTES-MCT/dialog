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
    private $locationNew;
    private $locationNewRepository;

    protected function setUp(): void
    {
        $this->locationNew = $this->createMock(Location::class);
        $this->locationNewRepository = $this->createMock(LocationRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->locationNewRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->locationNew));

        $handler = new DeleteLocationCommandHandler($this->locationNewRepository);

        $command = new DeleteLocationCommand($this->locationNew);
        $this->assertEmpty($handler($command));
    }
}

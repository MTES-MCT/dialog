<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteLocationNewCommand;
use App\Application\Regulation\Command\Location\DeleteLocationNewCommandHandler;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteLocationNewCommandHandlerTest extends TestCase
{
    private $locationNew;
    private $locationNewRepository;

    protected function setUp(): void
    {
        $this->locationNew = $this->createMock(LocationNew::class);
        $this->locationNewRepository = $this->createMock(LocationNewRepositoryInterface::class);
    }

    public function testDelete(): void
    {
        $this->locationNewRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($this->locationNew));

        $handler = new DeleteLocationNewCommandHandler($this->locationNewRepository);

        $command = new DeleteLocationNewCommand($this->locationNew);
        $this->assertEmpty($handler($command));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteNamedStreetCommand;
use App\Application\Regulation\Command\Location\DeleteNamedStreetCommandHandler;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteNamedStreetCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreetRepository = $this->createMock(NamedStreetRepositoryInterface::class);
        $namedStreetRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($namedStreet));

        $handler = new DeleteNamedStreetCommandHandler($namedStreetRepository);
        $command = new DeleteNamedStreetCommand($namedStreet);
        $this->assertEmpty($handler($command));
    }
}

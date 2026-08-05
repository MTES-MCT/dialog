<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteZoneCommand;
use App\Application\Regulation\Command\Location\DeleteZoneCommandHandler;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteZoneCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $zone = $this->createMock(Zone::class);

        $zoneRepository = $this->createMock(ZoneRepositoryInterface::class);
        $zoneRepository
            ->expects(self::once())
            ->method('delete')
            ->with($zone);

        $handler = new DeleteZoneCommandHandler($zoneRepository);

        $this->assertEmpty($handler(new DeleteZoneCommand($zone)));
    }
}

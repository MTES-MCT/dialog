<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\Query\Location\GetLocationByUuidQueryHandler;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetLocationByUuidQueryHandlerTest extends TestCase
{
    public function testGetLocation(): void
    {
        $location = $this->createMock(Location::class);
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('db3e218a-ac77-4245-bb80-9b23fc1a2d4e')
            ->willReturn($location);

        $handler = new GetLocationByUuidQueryHandler($locationRepository);

        $this->assertSame($location, $handler(new GetLocationByUuidQuery('db3e218a-ac77-4245-bb80-9b23fc1a2d4e')));
    }
}

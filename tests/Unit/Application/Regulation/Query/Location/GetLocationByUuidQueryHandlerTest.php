<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Query\Location\GetLocationByUuidQuery;
use App\Application\Regulation\Query\Location\GetLocationByUuidQueryHandler;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetLocationByUuidQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $location = $this->createMock(Location::class);
        $locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $locationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($location);

        $handler = new GetLocationByUuidQueryHandler($locationRepository);
        $result = $handler(new GetLocationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($location, $result);
    }
}

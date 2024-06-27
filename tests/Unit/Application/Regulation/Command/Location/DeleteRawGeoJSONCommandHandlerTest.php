<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\DeleteRawGeoJSONCommandHandler;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteRawGeoJSONCommandHandlerTest extends TestCase
{
    public function testDelete(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSONRepository = $this->createMock(RawGeoJSONRepositoryInterface::class);
        $rawGeoJSONRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->equalTo($rawGeoJSON));

        $handler = new DeleteRawGeoJSONCommandHandler($rawGeoJSONRepository);
        $command = new DeleteRawGeoJSONCommand($rawGeoJSON);
        $this->assertEmpty($handler($command));
    }
}

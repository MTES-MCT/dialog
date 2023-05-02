<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteRegulationLocationCommand;
use App\Application\Regulation\Command\Location\DeleteRegulationLocationCommandHandler;
use App\Domain\Regulation\Exception\LocationCannotBeDeletedException;
use App\Domain\Regulation\Exception\LocationDoesntBelongsToRegulationOrderException;
use App\Domain\Regulation\Exception\LocationNotFoundException;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanDeleteLocations;
use App\Infrastructure\Persistence\Doctrine\Repository\Regulation\LocationRepository;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationLocationCommandHandlerTest extends TestCase
{
    private $locationRepository;
    private $location;
    private $regulationOrderRecord;
    private $regulationOrder;
    private $canDeleteLocations;
    private $command;

    protected function setUp(): void
    {
        $this->locationRepository = $this->createMock(LocationRepository::class);
        $this->location = $this->createMock(Location::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->canDeleteLocations = $this->createMock(CanDeleteLocations::class);
        $this->command = new DeleteRegulationLocationCommand(
            '0a7badfa-9d84-42f3-b4f0-e83ef2a3d03c',
            $this->regulationOrderRecord,
        );
    }

    public function testDelete(): void
    {
        $this->canDeleteLocations
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(true);

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $this->locationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->willReturn($this->location);

        $this->location
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $this->locationRepository
            ->expects(self::once())
            ->method('delete')
            ->with($this->location);

        $handler = new DeleteRegulationLocationCommandHandler($this->locationRepository, $this->canDeleteLocations);
        $this->assertEmpty($handler($this->command));
    }

    public function testLocationDoesntBelongsToRegulationOrder(): void
    {
        $this->expectException(LocationDoesntBelongsToRegulationOrderException::class);

        $regulationOrder = $this->createMock(RegulationOrder::class);

        $this->canDeleteLocations
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(true);

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);

        $this->locationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->willReturn($this->location);

        $this->location
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $this->locationRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new DeleteRegulationLocationCommandHandler($this->locationRepository, $this->canDeleteLocations);
        $this->assertEmpty($handler($this->command));
    }

    public function testLocationNotFound(): void
    {
        $this->expectException(LocationNotFoundException::class);

        $this->canDeleteLocations
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(true);

        $this->locationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->willReturn(null);

        $this->locationRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new DeleteRegulationLocationCommandHandler($this->locationRepository, $this->canDeleteLocations);
        $this->assertEmpty($handler($this->command));
    }

    public function testLocationCannotBeDeleted(): void
    {
        $this->expectException(LocationCannotBeDeletedException::class);

        $this->canDeleteLocations
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->regulationOrderRecord)
            ->willReturn(false);

        $this->locationRepository
            ->expects(self::never())
            ->method('findOneByUuid');

        $this->locationRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new DeleteRegulationLocationCommandHandler($this->locationRepository, $this->canDeleteLocations);
        $this->assertEmpty($handler($this->command));
    }
}

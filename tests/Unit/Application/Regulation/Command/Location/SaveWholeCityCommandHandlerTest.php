<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommandHandler;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCity;
use App\Domain\Regulation\Location\WholeCityException;
use App\Domain\Regulation\Repository\WholeCityRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveWholeCityCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $wholeCityRepository;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->wholeCityRepository = $this->createMock(WholeCityRepositoryInterface::class);
    }

    public function testCreate(): void
    {
        $this->idFactory
            ->method('make')
            ->willReturnOnConsecutiveCalls('whole-city-uuid', 'exception-uuid');

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('setWholeCity');

        $this->wholeCityRepository
            ->expects(self::once())
            ->method('add')
            ->willReturnArgument(0);

        $handler = new SaveWholeCityCommandHandler(
            $this->idFactory,
            $this->wholeCityRepository,
        );

        $command = new SaveWholeCityCommand();
        $command->location = $location;
        $command->roadType = RoadTypeEnum::WHOLE_CITY->value;
        $command->cityCode = '59350';
        $command->cityLabel = 'Lille';

        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadBanId = '59350_1234';
        $exception->roadName = 'Rue de Paris';
        $command->exceptions = [$exception];

        $result = $handler($command);

        $this->assertInstanceOf(WholeCity::class, $result);
        $this->assertSame('whole-city-uuid', $result->getUuid());
        $this->assertSame('59350', $result->getCityCode());
        $this->assertSame('Lille', $result->getCityLabel());
        $this->assertCount(1, $result->getExceptions());
        $this->assertSame('Rue de Paris', $result->getExceptions()[0]->getRoadName());
        $this->assertSame('59350_1234', $result->getExceptions()[0]->getRoadBanId());
    }

    public function testUpdateReplacesExceptions(): void
    {
        $location = $this->createMock(Location::class);
        $wholeCity = new WholeCity('whole-city-uuid', $location, '59350', 'Lille');
        $wholeCity->addException(new WholeCityException('old-exception', $wholeCity, 'old_ban', 'Ancienne rue'));

        $this->idFactory
            ->method('make')
            ->willReturn('new-exception-uuid');

        $this->wholeCityRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveWholeCityCommandHandler(
            $this->idFactory,
            $this->wholeCityRepository,
        );

        $command = new SaveWholeCityCommand($wholeCity);
        $command->cityCode = '59350';
        $command->cityLabel = 'Lille Métropole';

        $newException = new SaveWholeCityExceptionCommand();
        $newException->roadBanId = 'new_ban';
        $newException->roadName = 'Rue Neuve';
        $command->exceptions = [$newException];

        $result = $handler($command);

        $this->assertSame($wholeCity, $result);
        $this->assertSame('Lille Métropole', $result->getCityLabel());
        $this->assertCount(1, $result->getExceptions());
        $this->assertSame('Rue Neuve', $result->getExceptions()[0]->getRoadName());
    }

    public function testCreateDropsIncompleteExceptions(): void
    {
        $this->idFactory
            ->method('make')
            ->willReturn('whole-city-uuid');

        $location = $this->createMock(Location::class);
        $this->wholeCityRepository
            ->method('add')
            ->willReturnArgument(0);

        $handler = new SaveWholeCityCommandHandler(
            $this->idFactory,
            $this->wholeCityRepository,
        );

        $command = new SaveWholeCityCommand();
        $command->location = $location;
        $command->roadType = RoadTypeEnum::WHOLE_CITY->value;
        $command->cityCode = '59350';
        $command->cityLabel = 'Lille';

        $blank = new SaveWholeCityExceptionCommand();
        $blank->roadName = 'Saisie incomplète sans roadBanId';
        $command->exceptions = [$blank];

        $result = $handler($command);

        $this->assertCount(0, $result->getExceptions());
    }
}

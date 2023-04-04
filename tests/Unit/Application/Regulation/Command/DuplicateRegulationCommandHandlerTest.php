<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Exception\RegulationCannotBeDuplicated;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $translator;
    private $canOrganizationAccessToRegulation;
    private $queryBus;
    private $commandBus;
    private $locationRepository;
    private $originalRegulationOrderRecord;
    private $organization;
    private $originalRegulationOrder;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->canOrganizationAccessToRegulation = $this->createMock(CanOrganizationAccessToRegulation::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->originalRegulationOrder = $this->createMock(RegulationOrder::class);

        $this->originalRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);
    }

    public function testRegulationCannotBeDuplicated(): void
    {
        $this->expectException(RegulationCannotBeDuplicated::class);

        $this->canOrganizationAccessToRegulation
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->originalRegulationOrderRecord, $this->organization)
            ->willReturn(false);

        $this->locationRepository
            ->expects(self::never())
            ->method('save');

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $command = new DuplicateRegulationCommand($this->organization, $this->originalRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canOrganizationAccessToRegulation,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $this->assertEmpty($handler($command));
    }

    public function testRegulationFullyDuplicated(): void
    {
        $startDate = new \DateTimeImmutable('2023-03-13');
        $endDate = new \DateTimeImmutable('2023-03-16');

        $this->canOrganizationAccessToRegulation
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->originalRegulationOrderRecord, $this->organization)
            ->willReturn(true);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);

        $duplicatedRegulationOrder = $this->createMock(RegulationOrder::class);

        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $duplicatedRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($duplicatedRegulationOrder);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.identifier.copy', [
                '%identifier%' => 'F01/2023',
            ])
            ->willReturn('F01/2023 (copie)');

        // Condition, RegulationOrder and RegulationOrderRecord
        $step1command = new SaveRegulationOrderCommand();
        $step1command->identifier = 'F01/2023 (copie)';
        $step1command->description = 'Description';
        $step1command->startDate = $startDate;
        $step1command->endDate = $endDate;
        $step1command->organization = $this->organization;

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('2d2e886c-90a3-4cd5-b0bf-ae288ca45830');

        // Location
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn('Route du Grand Brossais 44260 Savenay');
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn('15');
        $location
            ->expects(self::once())
            ->method('getFromPoint')
            ->willReturn('POINT(-1.935836 47.347024)');
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn('37bis');
        $location
            ->expects(self::once())
            ->method('getToPoint')
            ->willReturn('POINT(-1.930973 47.347917)');

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8');

        $this->locationRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                new Location(
                    uuid: '4430a28a-f9ad-4c4b-ba66-ce9cc9adb7d8',
                    regulationOrder: $duplicatedRegulationOrder,
                    address: 'Route du Grand Brossais 44260 Savenay',
                    fromHouseNumber: '15',
                    fromPoint: 'POINT(-1.935836 47.347024)',
                    toHouseNumber: '37bis',
                    toPoint: 'POINT(-1.930973 47.347917)',
                )
            );

        $this->queryBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->with(new GetLocationByRegulationOrderQuery('2d2e886c-90a3-4cd5-b0bf-ae288ca45830'))
            ->willReturn($location);

        $this->commandBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->with($step1command)
            ->willReturn($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canOrganizationAccessToRegulation,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $command = new DuplicateRegulationCommand($this->organization, $this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }

    public function testRegulationPartiallyDuplicated(): void
    {
        $startDate = new \DateTimeImmutable('2023-03-13');
        $endDate = new \DateTimeImmutable('2023-03-16');

        $this->canOrganizationAccessToRegulation
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->originalRegulationOrderRecord, $this->organization)
            ->willReturn(true);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('F01/2023');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($startDate);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($endDate);

        $duplicatedRegulationOrder = $this->createMock(RegulationOrder::class);
        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $duplicatedRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($duplicatedRegulationOrder);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.identifier.copy', [
                '%identifier%' => 'F01/2023',
            ])
            ->willReturn('F01/2023 (copie)');

        // RegulationOrder and RegulationOrderRecord
        $step1command = new SaveRegulationOrderCommand();
        $step1command->identifier = 'F01/2023 (copie)';
        $step1command->description = 'Description';
        $step1command->startDate = $startDate;
        $step1command->organization = $this->organization;
        $step1command->endDate = $endDate;

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('2d2e886c-90a3-4cd5-b0bf-ae288ca45830');

        $this->locationRepository
            ->expects(self::never())
            ->method('save');

        $this->queryBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->with(new GetLocationByRegulationOrderQuery('2d2e886c-90a3-4cd5-b0bf-ae288ca45830'))
            ->willReturn(null);

        $this->commandBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->with($step1command)
            ->willReturn($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canOrganizationAccessToRegulation,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $command = new DuplicateRegulationCommand($this->organization, $this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}

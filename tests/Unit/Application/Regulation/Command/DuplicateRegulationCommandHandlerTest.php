<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQuery;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DuplicateRegulationCommand;
use App\Application\Regulation\Command\DuplicateRegulationCommandHandler;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Domain\Condition\Location;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\LocationRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\Exception\RegulationCannotBeDuplicated;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationBeDuplicated;
use App\Domain\User\Organization;
use App\Infrastructure\Security\SymfonyUser;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $translator;
    private $canRegulationBeDuplicated;
    private $queryBus;
    private $commandBus;
    private $locationRepository;
    private $originalRegulationOrderRecord;
    private $organization;
    private $user;
    private $originalRegulationOrder;
    private $originalRegulationCondition;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->canRegulationBeDuplicated = $this->createMock(CanRegulationBeDuplicated::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->user = $this->createMock(SymfonyUser::class);
        $this->user
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->originalRegulationCondition = $this->createMock(RegulationCondition::class);

        $this->originalRegulationOrder = $this->createMock(RegulationOrder::class);
        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($this->originalRegulationCondition);

        $this->originalRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->originalRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->originalRegulationOrder);
    }

    public function testReglementationCannotBeDuplicated(): void
    {
        $this->expectException(RegulationCannotBeDuplicated::class);

        $this->canRegulationBeDuplicated
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

        $command = new DuplicateRegulationCommand($this->user, $this->originalRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canRegulationBeDuplicated,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $this->assertEmpty($handler($command));
    }

    public function testReglementationFullyDuplicated(): void
    {
        $this->canRegulationBeDuplicated
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->originalRegulationOrderRecord, $this->organization)
            ->willReturn(true);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité compétente');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $duplicatedRegulationCondition = $this->createMock(RegulationCondition::class);
        $duplicatedRegulationOrder = $this->createMock(RegulationOrder::class);
        $duplicatedRegulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($duplicatedRegulationCondition);

        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $duplicatedRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($duplicatedRegulationOrder);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.description.copy', [
                '%description%' => 'Description',
            ])
            ->willReturn('Description (copie)');

        // RegulationCondition, RegulationOrder and RegulationOrderRecord
        $step1command = new SaveRegulationStep1Command($this->user);
        $step1command->issuingAuthority = 'Autorité compétente';
        $step1command->description = 'Description (copie)';

        $this->originalRegulationCondition
            ->expects(self::exactly(3))
            ->method('getUuid')
            ->willReturn('d71d7c51-2a4b-49e2-8746-436466db1ade');

        // Location
        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getPostalCode')
            ->willReturn('44260');
        $location
            ->expects(self::once())
            ->method('getCity')
            ->willReturn('Savenay');
        $location
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn('Route du Grand Brossais');
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
                    regulationCondition: $duplicatedRegulationCondition,
                    postalCode: '44260',
                    city: 'Savenay',
                    roadName: 'Route du Grand Brossais',
                    fromHouseNumber: '15',
                    fromPoint: 'POINT(-1.935836 47.347024)',
                    toHouseNumber: '37bis',
                    toPoint: 'POINT(-1.930973 47.347917)',
                )
            );

        // OverallPeriod
        $start = new \DateTime('2023-02-10');
        $end = new \DateTime('2023-02-12');

        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriod
            ->expects(self::once())
            ->method('getStartPeriod')
            ->willReturn($start);
        $overallPeriod
            ->expects(self::once())
            ->method('getEndPeriod')
            ->willReturn($end);

        $step3Command = new SaveRegulationStep3Command($duplicatedRegulationOrderRecord);
        $step3Command->startPeriod = $start;
        $step3Command->endPeriod = $end;

        // VehicleCharacteristics
        $vehicleCharacteristics = $this->createMock(VehicleCharacteristics::class);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxHeight')
            ->willReturn(2.0);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxWeight')
            ->willReturn(3.5);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxWidth')
            ->willReturn(2.0);
        $vehicleCharacteristics
            ->expects(self::once())
            ->method('getMaxLength')
            ->willReturn(10.0);

        $step4Command = new SaveRegulationStep4Command($duplicatedRegulationOrderRecord);
        $step4Command->maxHeight = 2.0;
        $step4Command->maxLength = 10.0;
        $step4Command->maxWeight = 3.5;
        $step4Command->maxWidth = 2.0;

        $this->queryBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive(
                [new GetLocationByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
                [new GetOverallPeriodByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
                [new GetVehicleCharacteristicsByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
            )
            ->willReturnOnConsecutiveCalls($location, $overallPeriod, $vehicleCharacteristics);

        $this->commandBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive([$step1command], [$step3Command], [$step4Command])
            ->willReturnOnConsecutiveCalls($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canRegulationBeDuplicated,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $command = new DuplicateRegulationCommand($this->user, $this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }

    public function testReglementationPartiallyDuplicated(): void
    {
        $this->canRegulationBeDuplicated
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($this->originalRegulationOrderRecord, $this->organization)
            ->willReturn(true);

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité compétente');

        $this->originalRegulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $duplicatedRegulationCondition = $this->createMock(RegulationCondition::class);
        $duplicatedRegulationOrder = $this->createMock(RegulationOrder::class);
        $duplicatedRegulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($duplicatedRegulationCondition);

        $duplicatedRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $duplicatedRegulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($duplicatedRegulationOrder);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('regulation.description.copy', [
                '%description%' => 'Description',
            ])
            ->willReturn('Description (copie)');

        // RegulationCondition, RegulationOrder and RegulationOrderRecord
        $step1command = new SaveRegulationStep1Command($this->user);
        $step1command->issuingAuthority = 'Autorité compétente';
        $step1command->description = 'Description (copie)';

        $this->originalRegulationCondition
            ->expects(self::exactly(3))
            ->method('getUuid')
            ->willReturn('d71d7c51-2a4b-49e2-8746-436466db1ade');

        $this->locationRepository
            ->expects(self::never())
            ->method('save');

        $this->queryBus
            ->expects(self::exactly(3))
            ->method('handle')
            ->withConsecutive(
                [new GetLocationByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
                [new GetOverallPeriodByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
                [new GetVehicleCharacteristicsByRegulationConditionQuery('d71d7c51-2a4b-49e2-8746-436466db1ade')],
            )
            ->willReturnOnConsecutiveCalls(null, null, null);

        $this->commandBus
            ->expects(self::exactly(1))
            ->method('handle')
            ->withConsecutive([$step1command])
            ->willReturnOnConsecutiveCalls($duplicatedRegulationOrderRecord);

        $handler = new DuplicateRegulationCommandHandler(
            $this->idFactory,
            $this->translator,
            $this->canRegulationBeDuplicated,
            $this->queryBus,
            $this->commandBus,
            $this->locationRepository,
        );

        $command = new DuplicateRegulationCommand($this->user, $this->originalRegulationOrderRecord);
        $this->assertSame($duplicatedRegulationOrderRecord, $handler($command));
    }
}

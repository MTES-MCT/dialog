<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use App\Application\Regulation\Command\SaveRegulationOrderCommandHandler;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Exception\OrganizationAlreadyHasRegulationOrderWithThisIdentifierException;
use App\Domain\User\Organization;
use App\Domain\User\Specification\DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;
use PHPUnit\Framework\TestCase;

final class SaveRegulationOrderCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $regulationOrderRecordRepository;
    private $regulationOrderRepository;
    private $organization;
    private $doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier = $this->createMock(DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier::class);
    }

    public function testCreate(): void
    {
        $now = new \DateTimeImmutable('2022-01-09');
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $this->idFactory
            ->expects(self::exactly(2))
            ->method('make')
            ->willReturn(
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
            );

        $createdRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $this->regulationOrderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        identifier: 'FO2/2023',
                        description: 'Interdiction de circuler',
                        startDate: $start,
                        endDate: $end,
                    )
                )
            )
            ->willReturn($createdRegulationOrder);

        $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('FO2/2023', $this->organization)
            ->willReturn(false);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrderRecord(
                        uuid: 'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                        status: RegulationOrderRecordStatusEnum::DRAFT,
                        regulationOrder: $createdRegulationOrder,
                        createdAt: $now,
                        organization: $this->organization,
                    )
                )
            )
            ->willReturn($createdRegulationOrderRecord);

        $handler = new SaveRegulationOrderCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
            $now,
        );

        $command = new SaveRegulationOrderCommand();
        $command->identifier = 'FO2/2023';
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;
        $command->organization = $this->organization;

        $result = $handler($command);

        $this->assertSame($createdRegulationOrderRecord, $result);
    }

    public function testCreateWithIdentifierThatAlreadyExist(): void
    {
        $this->expectException(OrganizationAlreadyHasRegulationOrderWithThisIdentifierException::class);
        $now = new \DateTimeImmutable('2022-01-09');
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $this->idFactory
            ->expects(self::never())
            ->method('make')
           ;

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('save');

        $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('FO2/2023', $this->organization)
            ->willReturn(true);

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('save');

        $handler = new SaveRegulationOrderCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
            $now,
        );

        $command = new SaveRegulationOrderCommand();
        $command->identifier = 'FO2/2023';
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;
        $command->organization = $this->organization;

        $handler($command);
    }

    public function testUpdateWithSameIdentifierAndOrganization(): void
    {
        $now = new \DateTimeImmutable('2022-01-09');
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $createdRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $createdRegulationOrderRecord
            ->expects(self::never())
            ->method('getUuid');

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('save');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('FO2/2030');
        $regulationOrder
            ->expects(self::once())
            ->method('update')
            ->with(
                'FO2/2030',
                'Interdiction de circuler',
                new \DateTimeImmutable('2023-03-13'),
                new \DateTimeImmutable('2023-03-15'),

            );

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::exactly(2))
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($this->organization);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateOrganization')
            ->with($this->organization);

        $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $handler = new SaveRegulationOrderCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
            $now,
        );

        $command = new SaveRegulationOrderCommand($regulationOrderRecord);
        $command->identifier = 'FO2/2030';
        $command->organization = $this->organization;
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;

        $result = $handler($command);

        $this->assertSame($regulationOrderRecord, $result);
    }

    public function testUpdateWithDifferentIdentifierAndOrganization(): void
    {
        $now = new \DateTimeImmutable('2022-01-09');
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');
        $organization = $this->createMock(Organization::class);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $createdRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $createdRegulationOrderRecord
            ->expects(self::never())
            ->method('getUuid');

        $this->regulationOrderRepository
            ->expects(self::never())
            ->method('save');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('FO2/2031');
        $regulationOrder
            ->expects(self::once())
            ->method('update')
            ->with(
                'FO2/2030',
                'Interdiction de circuler',
                new \DateTimeImmutable('2023-03-13'),
                new \DateTimeImmutable('2023-03-15'),

            );

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::exactly(2))
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateOrganization')
            ->with($organization);

        $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('FO2/2030', $organization)
            ->willReturn(false);

        $handler = new SaveRegulationOrderCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
            $now,
        );

        $command = new SaveRegulationOrderCommand($regulationOrderRecord);
        $command->identifier = 'FO2/2030';
        $command->organization = $organization;
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;

        $result = $handler($command);

        $this->assertSame($regulationOrderRecord, $result);
    }
}

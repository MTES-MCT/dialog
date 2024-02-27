<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommandHandler;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveRegulationGeneralInfoCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $regulationOrderRecordRepository;
    private $regulationOrderRepository;
    private $organization;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->organization = $this->createMock(Organization::class);
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
            ->method('add')
            ->with(
                $this->equalTo(
                    new RegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        identifier: 'FO2/2023',
                        category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
                        description: 'Interdiction de circuler',
                        startDate: $start,
                        endDate: $end,
                    ),
                ),
            )
            ->willReturn($createdRegulationOrder);

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new RegulationOrderRecord(
                        uuid: 'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                        source: RegulationOrderRecordSourceEnum::DIALOG->value,
                        status: RegulationOrderRecordStatusEnum::DRAFT,
                        regulationOrder: $createdRegulationOrder,
                        createdAt: $now,
                        organization: $this->organization,
                    ),
                ),
            )
            ->willReturn($createdRegulationOrderRecord);

        $handler = new SaveRegulationGeneralInfoCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = 'FO2/2023';
        $command->category = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;
        $command->organization = $this->organization;

        $result = $handler($command);

        $this->assertSame($createdRegulationOrderRecord, $result);
    }

    public function testUpdate(): void
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
            ->method('add');

        $this->regulationOrderRecordRepository
            ->expects(self::never())
            ->method('add');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('update')
            ->with(
                'FO2/2030',
                RegulationOrderCategoryEnum::OTHER->value,
                'Interdiction de circuler',
                new \DateTimeImmutable('2023-03-13'),
                new \DateTimeImmutable('2023-03-15'),
                'Trou en formation',
            );

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateOrganization')
            ->with($organization);

        $handler = new SaveRegulationGeneralInfoCommandHandler(
            $this->idFactory,
            $this->regulationOrderRepository,
            $this->regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationGeneralInfoCommand($regulationOrderRecord);
        $command->identifier = 'FO2/2030';
        $command->organization = $organization;
        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->description = 'Interdiction de circuler';
        $command->startDate = $start;
        $command->endDate = $end;
        $command->otherCategoryText = 'Trou en formation';

        $result = $handler($command);

        $this->assertSame($regulationOrderRecord, $result);
    }
}

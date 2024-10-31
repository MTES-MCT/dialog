<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Application\Organization\VisaModel\Query\GetVisaModelQuery;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommandHandler;
use App\Domain\Organization\VisaModel\VisaModel;
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
    private $queryBus;
    private $organization;
    private $visaModel;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->organization = $this->createMock(Organization::class);
        $this->visaModel = $this->createMock(VisaModel::class);
    }

    public function testCreate(): void
    {
        $now = new \DateTimeImmutable('2022-01-09');

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
                    ),
                ),
            )
            ->willReturn($createdRegulationOrder);

        $this->queryBus
                ->expects(self::never())
                ->method('handle');

        $this->regulationOrderRecordRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new RegulationOrderRecord(
                        uuid: 'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                        source: RegulationOrderRecordSourceEnum::DIALOG->value,
                        status: RegulationOrderRecordStatusEnum::DRAFT->value,
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
            $this->queryBus,
        );

        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = 'FO2/2023';
        $command->category = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $command->description = 'Interdiction de circuler';
        $command->organization = $this->organization;

        $result = $handler($command);

        $this->assertSame($createdRegulationOrderRecord, $result);
    }

    public function testUpdate(): void
    {
        $now = new \DateTimeImmutable('2022-01-09');
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

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(new GetVisaModelQuery('b748e11a-e76f-4aba-b94c-c9f08cabd7d6'))
            ->willReturn($this->visaModel);

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('update')
            ->with(
                'FO2/2030',
                RegulationOrderCategoryEnum::OTHER->value,
                'Interdiction de circuler',
                'Trou en formation',
                [],
                [],
                $this->visaModel,
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
            $this->queryBus,
        );

        $command = new SaveRegulationGeneralInfoCommand($regulationOrderRecord);
        $command->identifier = 'FO2/2030';
        $command->organization = $organization;
        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->description = 'Interdiction de circuler';
        $command->otherCategoryText = 'Trou en formation';
        $command->visaModelUuid = 'b748e11a-e76f-4aba-b94c-c9f08cabd7d6';

        $result = $handler($command);

        $this->assertSame($regulationOrderRecord, $result);
    }
}

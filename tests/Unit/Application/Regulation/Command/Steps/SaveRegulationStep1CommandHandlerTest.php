<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1CommandHandler;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep1CommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationConditionRepository = $this->createMock(RegulationConditionRepositoryInterface::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $organization = $this->createMock(Organization::class);
        $now = new \DateTimeImmutable('2022-01-09');

        $idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                'f331d768-ed8b-496d-81ce-b97008f338d0',
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
            );

        $regulationCondition = new RegulationCondition(
            uuid: 'f331d768-ed8b-496d-81ce-b97008f338d0',
            negate: false,
        );
        $regulationConditionRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->equalTo($regulationCondition))
            ->willReturn($regulationCondition);

        $createdRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrderRecord(
                        uuid: 'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                        status: RegulationOrderRecordStatusEnum::DRAFT,
                        createdAt: $now,
                        organization: $organization,
                    )
                )
            )
            ->willReturn($createdRegulationOrderRecord);

        $regulationOrderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        issuingAuthority: 'Ville de Paris',
                        description: 'Interdiction de circuler',
                        regulationOrderRecord: $createdRegulationOrderRecord,
                        regulationCondition: $regulationCondition,
                    )
                )
            )
            ->willReturn($createdRegulationOrder);

        $handler = new SaveRegulationStep1CommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
            $regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationStep1Command($organization);
        $command->issuingAuthority = 'Ville de Paris';
        $command->description = 'Interdiction de circuler';

        $result = $handler($command);

        $this->assertSame($createdRegulationOrder, $result);
    }

    public function testUpdate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationConditionRepository = $this->createMock(RegulationConditionRepositoryInterface::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $now = new \DateTimeImmutable('2022-01-09');
        $organization = $this->createMock(Organization::class);

        $idFactory
            ->expects(self::never())
            ->method('make');

        $regulationConditionRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrderRecordRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrderRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('update')
            ->with(
                'Ville de Paris',
                'Interdiction de circuler',
            );

        $handler = new SaveRegulationStep1CommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
            $regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationStep1Command($organization, $regulationOrder);
        $command->issuingAuthority = 'Ville de Paris';
        $command->description = 'Interdiction de circuler';

        $result = $handler($command);

        $this->assertSame($regulationOrder, $result);
    }
}

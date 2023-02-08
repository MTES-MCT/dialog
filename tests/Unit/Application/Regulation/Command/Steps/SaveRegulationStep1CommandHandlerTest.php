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
use App\Infrastructure\Security\SymfonyUser;
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

        $user = $this->createMock(SymfonyUser::class);
        $user->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $now = new \DateTimeImmutable('2022-01-09');

        $idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                'f331d768-ed8b-496d-81ce-b97008f338d0',
                'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
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
        $createdRegulationOrderRecord
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f40f95eb-a7dd-4232-9f03-2db10f04f37f');

        $createdRegulationOrder = $this->createMock(RegulationOrder::class);

        $regulationOrderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrder(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        issuingAuthority: 'Ville de Paris',
                        description: 'Interdiction de circuler',
                        regulationCondition: $regulationCondition,
                    )
                )
            )
            ->willReturn($createdRegulationOrder);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new RegulationOrderRecord(
                        uuid: 'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
                        status: RegulationOrderRecordStatusEnum::DRAFT,
                        regulationOrder: $createdRegulationOrder,
                        createdAt: $now,
                        organization: $organization,
                    )
                )
            )
            ->willReturn($createdRegulationOrderRecord);

        $handler = new SaveRegulationStep1CommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
            $regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationStep1Command($user);
        $command->issuingAuthority = 'Ville de Paris';
        $command->description = 'Interdiction de circuler';

        $uuid = $handler($command);

        $this->assertSame('f40f95eb-a7dd-4232-9f03-2db10f04f37f', $uuid);
    }

    public function testUpdate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $regulationConditionRepository = $this->createMock(RegulationConditionRepositoryInterface::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);
        $user = $this->createMock(SymfonyUser::class);
        $now = new \DateTimeImmutable('2022-01-09');

        $idFactory
            ->expects(self::never())
            ->method('make');

        $regulationCondition = new RegulationCondition(
            uuid: 'f331d768-ed8b-496d-81ce-b97008f338d0',
            negate: false,
        );
        $regulationConditionRepository
            ->expects(self::never())
            ->method('save');

        $createdRegulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $createdRegulationOrderRecord
            ->expects(self::never())
            ->method('getUuid');

        $regulationOrderRepository
            ->expects(self::never())
            ->method('save');

        $regulationOrderRecordRepository
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

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f40f95eb-a7dd-4232-9f03-2db10f04f37f');

        $handler = new SaveRegulationStep1CommandHandler(
            $idFactory,
            $regulationConditionRepository,
            $regulationOrderRepository,
            $regulationOrderRecordRepository,
            $now,
        );

        $command = new SaveRegulationStep1Command($user, $regulationOrderRecord);
        $command->issuingAuthority = 'Ville de Paris';
        $command->description = 'Interdiction de circuler';

        $uuid = $handler($command);

        $this->assertSame('f40f95eb-a7dd-4232-9f03-2db10f04f37f', $uuid);
    }
}

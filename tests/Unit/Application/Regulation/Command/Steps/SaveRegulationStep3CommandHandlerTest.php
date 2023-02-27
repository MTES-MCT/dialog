<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Steps;

use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3CommandHandler;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep3CommandHandlerTest extends TestCase
{
    private $startDate;
    private $startTime;
    private $endDate;
    private $endTime;
    private $regulationCondition;
    private $regulationOrder;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->startDate = new \DateTime('2022-12-07');
        $this->startTime = new \DateTime('15:00');
        $this->endDate = new \DateTime('2022-12-17');
        $this->endTime = new \DateTime('16:00');

        $this->regulationCondition = $this->createMock(RegulationCondition::class);
        $this->regulationOrder = $this->createMock(RegulationOrder::class);
        $this->regulationOrder
            ->expects(self::once())
            ->method('getRegulationCondition')
            ->willReturn($this->regulationCondition);

        $this->regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($this->regulationOrder);
    }

    public function testCreate(): void
    {
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('98d3d3c6-83cd-49ab-b94a-4d4373a31114');

        $overallPeriodRepository = $this->createMock(OverallPeriodRepositoryInterface::class);
        $overallPeriodRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->equalTo(
                    new OverallPeriod(
                        uuid: '98d3d3c6-83cd-49ab-b94a-4d4373a31114',
                        regulationCondition: $this->regulationCondition,
                        startDate: $this->startDate,
                        startTime: $this->startTime,
                        endDate: $this->endDate,
                        endTime: $this->endTime,
                    )
                )
            );

        $handler = new SaveRegulationStep3CommandHandler(
            $idFactory,
            $overallPeriodRepository,
        );

        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startDate = $this->startDate;
        $command->startTime = $this->startTime;
        $command->endDate = $this->endDate;
        $command->endTime = $this->endTime;

        $this->assertEmpty($handler($command, null));
    }

    public function testUpdate(): void
    {
        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriod
            ->expects(self::once())
            ->method('update')
            ->with($this->startDate, $this->startTime, $this->endDate, $this->endTime);

        $idFactory = $this->createMock(IdFactoryInterface::class);
        $idFactory
            ->expects(self::never())
            ->method('make');

        $overallPeriodRepository = $this->createMock(OverallPeriodRepositoryInterface::class);
        $overallPeriodRepository
            ->expects(self::never())
            ->method('save');

        $handler = new SaveRegulationStep3CommandHandler(
            $idFactory,
            $overallPeriodRepository,
        );

        $command = new SaveRegulationStep3Command($this->regulationOrderRecord, $overallPeriod);
        $command->startDate = $this->startDate;
        $command->startTime = $this->startTime;
        $command->endDate = $this->endDate;
        $command->endTime = $this->endTime;

        $this->assertEmpty($handler($command));
    }
}

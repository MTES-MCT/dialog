<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

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
    private $startPeriod;
    private $endPeriod;
    private $regulationCondition;
    private $regulationOrder;
    private $regulationOrderRecord;

    protected function setUp(): void
    {
        $this->startPeriod = new \DateTime('2022-12-07 15:00');
        $this->endPeriod = new \DateTime('2022-12-17 16:00');

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

        $this->regulationOrderRecord
            ->expects(self::once())
            ->method('updateLastFilledStep')
            ->with(3);
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
                        startPeriod: $this->startPeriod,
                        endPeriod: $this->endPeriod,
                    )
                )
            );

        $handler = new SaveRegulationStep3CommandHandler(
            $idFactory,
            $overallPeriodRepository,
        );

        $command = new SaveRegulationStep3Command($this->regulationOrderRecord);
        $command->startPeriod = $this->startPeriod;
        $command->endPeriod = $this->endPeriod;

        $this->assertEmpty($handler($command, null));
    }

    public function testUpdate(): void
    {
        $overallPeriod = $this->createMock(OverallPeriod::class);
        $overallPeriod
            ->expects(self::once())
            ->method('update')
            ->with($this->startPeriod, $this->endPeriod);

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
        $command->startPeriod = $this->startPeriod;
        $command->endPeriod = $this->endPeriod;

        $this->assertEmpty($handler($command));
    }
}

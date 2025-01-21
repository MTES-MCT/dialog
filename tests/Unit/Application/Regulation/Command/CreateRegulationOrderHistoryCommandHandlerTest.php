<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\CreateRegulationOrderHistoryCommand;
use App\Application\Regulation\Command\CreateRegulationOrderHistoryCommandHandler;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\User;
use AsyncAws\Core\Test\TestCase;

final class CreateRegulationOrderHistoryCommandHandlerTest extends TestCase
{
    private $idFactory;
    private $regulationOrderHistoryRepository;
    private $dateUtils;

    public function SetUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderHistoryRepository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testCreate(): void
    {
        $now = new \DateTimeImmutable('2023-06-13');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('d035fec0-30f3-4134-95b9-d74c68eb53e3');

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $user = $this->createMock(User::class);
        $action = ActionTypeEnum::PUBLISH->value;

        $regulationOrder
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('9ddd73e5-2162-4279-be73-183816e7f85b');

        $user
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('3cc78eae-50ba-4805-9b75-7f64ca638caf');

        $createdRegulationOrderHistory = $this->createMock(RegulationOrderHistory::class);

        $this->regulationOrderHistoryRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                $this->equalTo(
                    new RegulationOrderHistory(
                        uuid: 'd035fec0-30f3-4134-95b9-d74c68eb53e3',
                        regulationOrderUuid: '9ddd73e5-2162-4279-be73-183816e7f85b',
                        userUuid: '3cc78eae-50ba-4805-9b75-7f64ca638caf',
                        action: $action,
                        date: $now,
                    ),
                ),
            )
            ->willReturn($createdRegulationOrderHistory);

        $handler = new CreateRegulationOrderHistoryCommandHandler(
            $this->idFactory,
            $this->regulationOrderHistoryRepository,
            $this->dateUtils,
        );

        $command = new CreateRegulationOrderHistoryCommand($regulationOrder,
            $user,
            $action);

        $result = $handler($command);

        $this->assertSame($createdRegulationOrderHistory, $result);
    }
}

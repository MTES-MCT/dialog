<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderHistoryQuery;
use App\Application\Regulation\Query\GetRegulationOrderHistoryQueryHandler;
use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderHistoryQueryHandlerTest extends TestCase
{
    public function testGetRegulationOrderHistory(): void
    {
        $mockDate = new \DateTime('2025-01-31 08:41:57');
        $userUuid = 'a3f1e6c8-1c4c-4e3b-aaa6-202d98a63b3a';
        $row = [
            'date' => $mockDate,
            'action' => 'create',
            'userUuid' => $userUuid,
        ];
        $repository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('findLastRegulationOrderHistoryByUuid')
            ->with('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a')
            ->willReturn($row);

        $user = (new User($userUuid))->setFullName('Mathieu Marchois');
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($userUuid)
            ->willReturn($user);

        $regulationOrderHistoryView = new RegulationOrderHistoryView(
            date: $mockDate,
            action: ActionTypeEnum::CREATE->value,
            userFullName: 'Mathieu Marchois',
        );

        $handler = new GetRegulationOrderHistoryQueryHandler($repository, $userRepository);
        $result = $handler(new GetRegulationOrderHistoryQuery('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a'));

        $this->assertEquals($regulationOrderHistoryView, $result);
    }

    public function testGetRegulationOrderHistoryWithDeletedUser(): void
    {
        $mockDate = new \DateTime('2025-01-31 08:41:57');
        $userUuid = 'a3f1e6c8-1c4c-4e3b-aaa6-202d98a63b3a';
        $row = [
            'date' => $mockDate,
            'action' => 'publish',
            'userUuid' => $userUuid,
        ];
        $repository = $this->createMock(RegulationOrderHistoryRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('findLastRegulationOrderHistoryByUuid')
            ->willReturn($row);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with($userUuid)
            ->willReturn(null);

        $handler = new GetRegulationOrderHistoryQueryHandler($repository, $userRepository);
        $result = $handler(new GetRegulationOrderHistoryQuery('c41d4831-1c4c-4e3b-aaa6-202d98a63b3a'));

        $this->assertNull($result->userFullName);
    }
}

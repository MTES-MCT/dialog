<?php
namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetUsersQuery;
use App\Application\User\Query\GetUsersQueryHandler;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class GetUsersQueryHandlerTest extends TestCase
{
    function testGetUsers(){

        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
        ->expects(self::once())
        ->method('findUsers')
        ->willReturn([$user1,$user2]);
        $handler = new GetUsersQueryHandler($userRepository);
        $result = $handler->__invoke(new GetUsersQuery());

        $this->assertEquals([$user1,$user2],$result);

    }
}
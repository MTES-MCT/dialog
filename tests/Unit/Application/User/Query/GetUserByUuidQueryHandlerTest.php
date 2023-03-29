<?php

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetUserByUuidQuery;
use App\Application\User\Query\GetUserByUuidQueryHandler;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class GetUserByUuidQueryHandlerTest extends TestCase
{
    function testGetUser()
    {
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
        ->expects(self::once())
            ->method('findUserByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($user);

        $handler = new GetUserByUuidQueryHandler($userRepository);
        $result = $handler->__invoke(new GetUserByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($user,$result);
    }
    }
   
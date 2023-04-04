<?php
namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetUsersQuery;
use App\Application\User\Query\GetUsersQueryHandler;
use App\Application\User\View\UserListView;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class GetUsersQueryHandlerTest extends TestCase
{
    function testGetUsers(){
        //PREMIERE METHODE SANS COUCHE VIEW
        // $user1 = $this->createMock(User::class);
        // $user2 = $this->createMock(User::class);
        // $userRepository = $this->createMock(UserRepositoryInterface::class);
        // $userRepository
        // ->expects(self::once())
        // ->method('findUsers')
        // ->willReturn([$user1,$user2]);
        // $handler = new GetUsersQueryHandler($userRepository);
        // $result = $handler->__invoke(new GetUsersQuery());
        // $this->assertEquals([$user1,$user2],$result);

        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
        ->expects(self::once())
        ->method('findUsers')
        ->willReturn([$user1,$user2]);

        $user1
        ->expects(self::once())
        ->method('getUuid')
        ->willReturn('d32b3680-95c6-4bc7-9119-3e71157410c2');
        $user1
        ->expects(self::once())
        ->method('getFullName')
        ->willReturn('Claire Lefoulon');
        $user1
        ->expects(self::once())
        ->method('getEmail')
        ->willReturn('claire@beta.gouv.fr');

        $user2
        ->expects(self::once())
        ->method('getUuid')
        ->willReturn('7115057a-30ad-4ed9-9c3e-42372834afee');
        $user2
        ->expects(self::once())
        ->method('getFullName')
        ->willReturn('Florimond MANCA');
        $user2
        ->expects(self::once())
        ->method('getEmail')
        ->willReturn('florimond.manca@beta.gouv.fr');
        
        $userListView = [
            new UserListView('d32b3680-95c6-4bc7-9119-3e71157410c2','Claire Lefoulon','claire@beta.gouv.fr' ), new UserListView('7115057a-30ad-4ed9-9c3e-42372834afee','Florimond MANCA','florimond.manca@beta.gouv.fr')
        ];
        // dd($userListView);
        $handler = new GetUsersQueryHandler($userRepository);
        $result = $handler->__invoke(new GetUsersQuery());
        $this->assertEquals($userListView,$result);
    }
}
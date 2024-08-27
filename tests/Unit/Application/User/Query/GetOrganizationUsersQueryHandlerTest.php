<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationUsersQuery;
use App\Application\User\Query\GetOrganizationUsersQueryHandler;
use App\Application\User\View\OrganizationUserView;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class GetOrganizationUsersQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $organizationUser1 = $this->createMock(OrganizationUser::class);
        $user1 = $this->createMock(User::class);
        $user1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('42a1888f-29cb-4e32-a02f-49d278b6d128');
        $user1
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu MARCHOIS');
        $user1
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('mathieu.marchois@beta.gouv.fr');
        $organizationUser1
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user1);

        $organizationUser2 = $this->createMock(OrganizationUser::class);
        $user2 = $this->createMock(User::class);
        $user2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('d732584b-810f-4932-bd83-41b60c24c414');
        $user2
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Mathieu FERNANDEZ');
        $user2
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('mathieu.fernandez@beta.gouv.fr');
        $organizationUser2
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user2);

        $expectedResults = [
            new OrganizationUserView('42a1888f-29cb-4e32-a02f-49d278b6d128', 'Mathieu MARCHOIS', 'mathieu.marchois@beta.gouv.fr'),
            new OrganizationUserView('d732584b-810f-4932-bd83-41b60c24c414', 'Mathieu FERNANDEZ', 'mathieu.fernandez@beta.gouv.fr'),
        ];

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findByOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([$organizationUser1, $organizationUser2]);

        $handler = new GetOrganizationUsersQueryHandler($organizationUserRepository);
        $result = $handler(new GetOrganizationUsersQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($expectedResults, $result);
    }
}

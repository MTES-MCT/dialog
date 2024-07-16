<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationUsersQuery;
use App\Application\User\Query\GetOrganizationUsersQueryHandler;
use App\Application\User\View\UserView;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationUsersQueryHandlerTest extends TestCase
{
    public function testGetUsers(): void
    {
        $expectedResults = [
            new UserView('42a1888f-29cb-4e32-a02f-49d278b6d128', 'Mathieu MARCHOIS', 'mathieu.marchois@beta.gouv.fr'),
            new UserView('d732584b-810f-4932-bd83-41b60c24c414', 'Mathieu FERNANDEZ', 'mathieu.fernandez@beta.gouv.fr'),
        ];

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findUsersByOrganizationUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($expectedResults);

        $handler = new GetOrganizationUsersQueryHandler($organizationUserRepository);
        $result = $handler(new GetOrganizationUsersQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($expectedResults, $result);
    }
}

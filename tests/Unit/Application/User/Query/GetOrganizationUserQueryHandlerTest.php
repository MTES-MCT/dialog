<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationUserQuery;
use App\Application\User\Query\GetOrganizationUserQueryHandler;
use App\Domain\User\Exception\OrganizationUserNotFoundException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationUserQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationUser')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf', 'f45be436-efca-4393-be7a-0712a884bccc')
            ->willReturn($organizationUser);

        $handler = new GetOrganizationUserQueryHandler($organizationUserRepository);
        $result = $handler(new GetOrganizationUserQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf', 'f45be436-efca-4393-be7a-0712a884bccc'));

        $this->assertEquals($organizationUser, $result);
    }

    public function testNotFound(): void
    {
        $this->expectException(OrganizationUserNotFoundException::class);

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationUser')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf', 'f45be436-efca-4393-be7a-0712a884bccc')
            ->willReturn(null);

        $handler = new GetOrganizationUserQueryHandler($organizationUserRepository);
        $handler(new GetOrganizationUserQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf', 'f45be436-efca-4393-be7a-0712a884bccc'));
    }
}

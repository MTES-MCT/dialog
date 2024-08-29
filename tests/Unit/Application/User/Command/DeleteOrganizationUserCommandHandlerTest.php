<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\DeleteOrganizationUserCommand;
use App\Application\User\Command\DeleteOrganizationUserCommandHandler;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class DeleteOrganizationUserCommandHandlerTest extends TestCase
{
    public function testDeleteUserBelongsToManyOrganizations(): void
    {
        $userUuid = '066cdc8d-b941-7de0-8000-c9401c9a8f24';
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($userUuid);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $organizationUserRepository
            ->expects(self::once())
            ->method('findByUserUuid')
            ->with($userUuid)
            ->willReturn(['org1', 'org2']);

        $organizationUserRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->equalTo($organizationUser));

        $userRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new DeleteOrganizationUserCommandHandler(
            $userRepository,
            $organizationUserRepository,
        );
        $command = new DeleteOrganizationUserCommand($organizationUser);
        $handler($command);
    }

    public function testDeleteUserBelongsToOneOrganization(): void
    {
        $userUuid = '066cdc8d-b941-7de0-8000-c9401c9a8f24';
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn($userUuid);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $organizationUserRepository
            ->expects(self::once())
            ->method('findByUserUuid')
            ->with($userUuid)
            ->willReturn(['org1']);

        $organizationUserRepository
            ->expects(self::never())
            ->method('remove');

        $userRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->equalTo($user));

        $handler = new DeleteOrganizationUserCommandHandler(
            $userRepository,
            $organizationUserRepository,
        );
        $command = new DeleteOrganizationUserCommand($organizationUser);
        $handler($command);
    }
}

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
        $user = $this->createMock(User::class);
        $organizationUser2 = $this->createMock(OrganizationUser::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationsByUser')
            ->with($this->equalTo($user))
            ->willReturn([
                $organizationUser2,
                $organizationUser,
            ]);

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
        $user = $this->createMock(User::class);
        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);

        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationsByUser')
            ->with($this->equalTo($user))
            ->willReturn([
                $organizationUser,
            ]);

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

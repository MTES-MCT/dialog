<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\SetUserAccountStatusCommand;
use App\Application\User\Command\SetUserAccountStatusCommandHandler;
use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SetUserAccountStatusCommandHandlerTest extends TestCase
{
    public function testValidate(): void
    {
        $user = new User('0de5692b-cab1-494c-804d-765dc14df674');
        $user->setAccountStatus(UserAccountStatusEnum::PENDING->value);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('0de5692b-cab1-494c-804d-765dc14df674')
            ->willReturn($user);

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $this->createMock(OrganizationUserRepositoryInterface::class));
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', UserAccountStatusEnum::VALIDATED->value));

        $this->assertSame(UserAccountStatusEnum::VALIDATED->value, $user->getAccountStatus());
        $this->assertTrue($user->isValidated());
    }

    public function testRefuse(): void
    {
        $user = new User('0de5692b-cab1-494c-804d-765dc14df674');
        $user->setAccountStatus(UserAccountStatusEnum::PENDING->value);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->willReturn($user);

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $this->createMock(OrganizationUserRepositoryInterface::class));
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', UserAccountStatusEnum::REFUSED->value));

        $this->assertSame(UserAccountStatusEnum::REFUSED->value, $user->getAccountStatus());
        $this->assertFalse($user->isValidated());
    }

    public function testUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->willReturn(null);

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $this->createMock(OrganizationUserRepositoryInterface::class));
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', UserAccountStatusEnum::VALIDATED->value));
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::never())
            ->method('findOneByUuid');

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $this->createMock(OrganizationUserRepositoryInterface::class));
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', 'not-a-status'));
    }

    public function testValidateGrantsOwnershipOfOrganizationWithoutOwner(): void
    {
        $userUuid = '0de5692b-cab1-494c-804d-765dc14df674';
        $user = (new User($userUuid))->setAccountStatus(UserAccountStatusEnum::PENDING->value);
        $orgUserWithoutOwner = (new OrganizationUser('8b8f7b3b-5e3a-4f7d-9b8e-1b6f0c6d2a11'))->setIsOwner(false);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findOneByUuid')->willReturn($user);

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->method('findByUserUuid')
            ->with($userUuid)
            ->willReturn([
                new UserOrganizationView('org-without-owner', 'Mairie A'),
                new UserOrganizationView('org-with-owner', 'Mairie B'),
            ]);
        $organizationUserRepository
            ->method('countOwnersByOrganizationUuid')
            ->willReturnMap([['org-without-owner', 0], ['org-with-owner', 1]]);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationUser')
            ->with('org-without-owner', $userUuid)
            ->willReturn($orgUserWithoutOwner);

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $organizationUserRepository);
        $handler(new SetUserAccountStatusCommand($userUuid, UserAccountStatusEnum::VALIDATED->value));

        $this->assertTrue($orgUserWithoutOwner->isOwner());
    }

    public function testRefuseRevokesOwnership(): void
    {
        $userUuid = '0de5692b-cab1-494c-804d-765dc14df674';
        $user = (new User($userUuid))->setAccountStatus(UserAccountStatusEnum::VALIDATED->value);
        $orgUser = (new OrganizationUser('8b8f7b3b-5e3a-4f7d-9b8e-1b6f0c6d2a11'))->setIsOwner(true);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findOneByUuid')->willReturn($user);

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->method('findByUserUuid')
            ->willReturn([new UserOrganizationView('org-uuid', 'Mairie A', isOwner: true)]);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findOrganizationUser')
            ->with('org-uuid', $userUuid)
            ->willReturn($orgUser);

        $handler = new SetUserAccountStatusCommandHandler($userRepository, $organizationUserRepository);
        $handler(new SetUserAccountStatusCommand($userUuid, UserAccountStatusEnum::REFUSED->value));

        $this->assertFalse($orgUser->isOwner());
    }
}

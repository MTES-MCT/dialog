<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Invitation;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\User\Command\Invitation\CreateAccountFromInvitationCommand;
use App\Application\User\Command\Invitation\CreateAccountFromInvitationCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateAccountFromInvitationCommandHandlerTest extends TestCase
{
    private MockObject $invitationRepository;
    private MockObject $userRepository;
    private MockObject $passwordUserRepository;
    private MockObject $organizationUserRepository;
    private MockObject $idFactory;
    private MockObject $dateUtils;
    private MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(InvitationRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordUserRepository = $this->createMock(PasswordUserRepositoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
    }

    public function testCreateAccountFromInvitation(): void
    {
        $now = new \DateTimeImmutable('2025-02-12');
        $organization = $this->createMock(Organization::class);
        $owner = $this->createMock(User::class);

        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('nouveau.utilisateur@example.com');
        $invitation
            ->expects(self::once())
            ->method('getFullName')
            ->willReturn('Nouveau Utilisateur');
        $invitation
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $invitation
            ->expects(self::once())
            ->method('getRole')
            ->willReturn(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $command = new CreateAccountFromInvitationCommand('invitation-uuid');
        $command->password = 'securePassword123';

        $this->invitationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('invitation-uuid')
            ->willReturn($invitation);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('nouveau.utilisateur@example.com')
            ->willReturn(null);

        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturnOnConsecutiveCalls('user-uuid', 'password-user-uuid', 'org-user-uuid');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('securePassword123')
            ->willReturn('hashedPassword');

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with(self::callback(function (User $user) use ($now) {
                return $user->getUuid() === 'user-uuid'
                    && $user->getFullName() === 'Nouveau Utilisateur'
                    && $user->getEmail() === 'nouveau.utilisateur@example.com'
                    && $user->getRoles() === [UserRolesEnum::ROLE_USER->value]
                    && $user->getRegistrationDate() === $now;
            }));

        $this->passwordUserRepository
            ->expects(self::once())
            ->method('add')
            ->with(self::callback(function (PasswordUser $passwordUser) {
                return $passwordUser->getUuid() === 'password-user-uuid'
                    && $passwordUser->getPassword() === 'hashedPassword';
            }));

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with(self::callback(function (OrganizationUser $orgUser) use ($organization) {
                return $orgUser->getOrganization() === $organization
                    && $orgUser->getRoles() === OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
            }));

        $this->invitationRepository
            ->expects(self::once())
            ->method('delete')
            ->with($invitation);

        $handler = new CreateAccountFromInvitationCommandHandler(
            $this->invitationRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->organizationUserRepository,
            $this->idFactory,
            $this->dateUtils,
            $this->passwordHasher,
        );

        $result = $handler($command);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('user-uuid', $result->getUuid());
    }

    public function testInvitationNotFound(): void
    {
        $this->expectException(InvitationNotFoundException::class);

        $command = new CreateAccountFromInvitationCommand('unknown-invitation-uuid');
        $command->password = 'securePassword123';

        $this->invitationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('unknown-invitation-uuid')
            ->willReturn(null);

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        $this->invitationRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new CreateAccountFromInvitationCommandHandler(
            $this->invitationRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->organizationUserRepository,
            $this->idFactory,
            $this->dateUtils,
            $this->passwordHasher,
        );

        $handler($command);
    }

    public function testUserAlreadyRegistered(): void
    {
        $this->expectException(UserAlreadyRegisteredException::class);

        $existingUser = $this->createMock(User::class);
        $invitation = $this->createMock(Invitation::class);
        $invitation
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn('existing.user@example.com');

        $command = new CreateAccountFromInvitationCommand('invitation-uuid');
        $command->password = 'securePassword123';

        $this->invitationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('invitation-uuid')
            ->willReturn($invitation);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('existing.user@example.com')
            ->willReturn($existingUser);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        $this->invitationRepository
            ->expects(self::never())
            ->method('delete');

        $handler = new CreateAccountFromInvitationCommandHandler(
            $this->invitationRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->organizationUserRepository,
            $this->idFactory,
            $this->dateUtils,
            $this->passwordHasher,
        );

        $handler($command);
    }
}

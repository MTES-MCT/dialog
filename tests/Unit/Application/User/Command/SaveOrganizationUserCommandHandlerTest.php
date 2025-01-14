<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\SaveOrganizationUserCommand;
use App\Application\User\Command\SaveOrganizationUserCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\IsEmailAlreadyExists;
use App\Domain\User\Specification\IsUserAlreadyRegisteredInOrganization;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveOrganizationUserCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $organizationUserRepository;
    private MockObject $passwordUserRepository;
    private MockObject $userRepository;
    private MockObject $stringUtils;
    private MockObject $dateUtils;
    private MockObject $passwordHasher;
    private MockObject $isUserAlreadyRegistered;
    private MockObject $isEmailAlreadyExists;
    private MockObject $organization;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordUserRepository = $this->createMock(PasswordUserRepositoryInterface::class);
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->isUserAlreadyRegistered = $this->createMock(IsUserAlreadyRegisteredInOrganization::class);
        $this->isEmailAlreadyExists = $this->createMock(IsEmailAlreadyExists::class);
        $this->organization = $this->createMock(Organization::class);

        $this->stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->willReturn('mathieu.marchois@beta.gouv.fr');
    }

    public function testCreateNewUserAccount(): void
    {
        $registrationDate = new \DateTimeImmutable('2024-08-06');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($registrationDate);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('password')
            ->willReturn('encryptedPassword');

        $this->isUserAlreadyRegistered
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu.marchois@beta.gouv.fr', $this->organization)
            ->willReturn(false);

        $this->isEmailAlreadyExists
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn(null);

        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                '584f12e7-01b1-4981-9bab-cf8dcce535b9',
                '93b30d2c-1a38-4f7e-ae1c-9721ebf7bef4',
                '0de5692b-cab1-494c-804d-765dc14df674',
            );

        $user = (new User('584f12e7-01b1-4981-9bab-cf8dcce535b9'))
            ->setEmail('mathieu.marchois@beta.gouv.fr')
            ->setFullName('Mathieu MARCHOIS')
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($registrationDate);

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($user))
            ->willReturn($user);

        $this->passwordUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo(new PasswordUser('93b30d2c-1a38-4f7e-ae1c-9721ebf7bef4', 'encryptedPassword', $user)));

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo(
                (new OrganizationUser('0de5692b-cab1-494c-804d-765dc14df674'))
                    ->setUser($user)
                    ->setOrganization($this->organization)
                    ->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value),
            ));

        $handler = new SaveOrganizationUserCommandHandler(
            $this->idFactory,
            $this->organizationUserRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->stringUtils,
            $this->dateUtils,
            $this->passwordHasher,
            $this->isUserAlreadyRegistered,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveOrganizationUserCommand($this->organization);
        $command->role = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $command->fullName = 'Mathieu MARCHOIS';
        $command->email = 'mathieu.marchois@beta.gouv.fr';
        $command->password = 'password';

        $handler($command);
    }

    public function testAddExistingUserInOrganization(): void
    {
        $user = $this->createMock(User::class);

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $this->isEmailAlreadyExists
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->isUserAlreadyRegistered
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu.marchois@beta.gouv.fr', $this->organization)
            ->willReturn(false);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn($user);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('0de5692b-cab1-494c-804d-765dc14df674');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo(
                (new OrganizationUser('0de5692b-cab1-494c-804d-765dc14df674'))
                    ->setUser($user)
                    ->setOrganization($this->organization)
                    ->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value),
            ));

        $handler = new SaveOrganizationUserCommandHandler(
            $this->idFactory,
            $this->organizationUserRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->stringUtils,
            $this->dateUtils,
            $this->passwordHasher,
            $this->isUserAlreadyRegistered,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveOrganizationUserCommand($this->organization);
        $command->role = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $command->fullName = 'Mathieu MARCHOIS';
        $command->email = 'mathieu.marchois@beta.gouv.fr';
        $command->password = 'password';

        $handler($command);
    }

    public function testCreateAlreadyExistingAccount(): void
    {
        $this->expectException(UserAlreadyRegisteredException::class);

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $this->isEmailAlreadyExists
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->isUserAlreadyRegistered
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu.marchois@beta.gouv.fr', $this->organization)
            ->willReturn(true);

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        $this->organizationUserRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveOrganizationUserCommandHandler(
            $this->idFactory,
            $this->organizationUserRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->stringUtils,
            $this->dateUtils,
            $this->passwordHasher,
            $this->isUserAlreadyRegistered,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveOrganizationUserCommand($this->organization);
        $command->role = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $command->fullName = 'Mathieu MARCHOIS';
        $command->email = 'mathieu.marchois@beta.gouv.fr';
        $command->password = 'password';

        $handler($command);
    }

    public function testUpdate(): void
    {
        $user = $this->createMock(User::class);
        $user
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('mathieu.marchois@beta.gouv.com');

        $user
            ->expects(self::once())
            ->method('setEmail')
            ->with('mathieu.marchois@beta.gouv.fr');
        $user
            ->expects(self::once())
            ->method('setFullName')
            ->with('Mathieu MARCHOIS');

        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::exactly(3))
            ->method('getUser')
            ->willReturn($user);
        $organizationUser
            ->expects(self::once())
            ->method('setRoles')
            ->with(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $this->isEmailAlreadyExists
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn(false);

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $this->isUserAlreadyRegistered
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveOrganizationUserCommandHandler(
            $this->idFactory,
            $this->organizationUserRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->stringUtils,
            $this->dateUtils,
            $this->passwordHasher,
            $this->isUserAlreadyRegistered,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveOrganizationUserCommand($this->organization, $organizationUser);
        $command->role = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $command->fullName = 'Mathieu MARCHOIS';
        $command->email = 'mathieu.marchois@beta.gouv.fr';
        $command->password = 'password';

        $handler($command);
    }

    public function testUpdateEmailAlreadyExist(): void
    {
        $this->expectException(EmailAlreadyExistsException::class);

        $user = $this->createMock(User::class);
        $user
            ->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn('mathieu.marchois@beta.gouv.com');

        $user
            ->expects(self::never())
            ->method('setEmail');
        $user
            ->expects(self::never())
            ->method('setFullName');

        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUser
            ->expects(self::exactly(3))
            ->method('getUser')
            ->willReturn($user);
        $organizationUser
            ->expects(self::never())
            ->method('setRoles');

        $this->isEmailAlreadyExists
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with('mathieu.marchois@beta.gouv.fr')
            ->willReturn(true);

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $this->isUserAlreadyRegistered
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->userRepository
            ->expects(self::never())
            ->method('findOneByEmail');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $handler = new SaveOrganizationUserCommandHandler(
            $this->idFactory,
            $this->organizationUserRepository,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->stringUtils,
            $this->dateUtils,
            $this->passwordHasher,
            $this->isUserAlreadyRegistered,
            $this->isEmailAlreadyExists,
        );
        $command = new SaveOrganizationUserCommand($this->organization, $organizationUser);
        $command->role = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;
        $command->fullName = 'Mathieu MARCHOIS';
        $command->email = 'mathieu.marchois@beta.gouv.fr';
        $command->password = 'password';

        $handler($command);
    }
}

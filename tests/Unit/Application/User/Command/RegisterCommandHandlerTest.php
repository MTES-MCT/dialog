<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\RegisterCommand;
use App\Application\User\Command\RegisterCommandHandler;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegisterCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $passwordHasher;
    private MockObject $userRepository;
    private MockObject $passwordUserRepository;
    private MockObject $organizationUserRepository;
    private MockObject $dateUtils;
    private MockObject $stringUtils;
    private MockObject $commandBus;
    private RegisterCommandHandler $handler;
    private RegisterCommand $command;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordUserRepository = $this->createMock(PasswordUserRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->stringUtils = $this->createMock(StringUtilsInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->handler = new RegisterCommandHandler(
            $this->idFactory,
            $this->userRepository,
            $this->passwordUserRepository,
            $this->organizationUserRepository,
            $this->dateUtils,
            $this->stringUtils,
            $this->passwordHasher,
            $this->commandBus,
        );

        $this->command = new RegisterCommand();
        $this->command->fullName = 'Mathieu MARCHOIS';
        $this->command->email = ' mathieu@fairness.coop ';
        $this->command->password = '12345';
        $this->command->organizationSiret = '82050375300015';

        $this->stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with(' mathieu@fairness.coop ')
            ->willReturn('mathieu@fairness.coop');
    }

    public function testRegister(): void
    {
        $organization = $this->createMock(Organization::class);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn(null);

        $orgView = new GetOrCreateOrganizationView($organization, false);
        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(function (GetOrCreateOrganizationBySiretCommand $cmd) {
                return $cmd->siret === '82050375300015';
            }))
            ->willReturn($orgView);

        $this->idFactory
            ->expects(self::exactly(3))
            ->method('make')
            ->willReturn(
                '0de5692b-cab1-494c-804d-765dc14df674',
                '3e4ea113-9f64-4933-a699-0561b8c15622',
                'f40f95eb-a7dd-4232-9f03-2db10f04f37f',
            );

        $date = new \DateTimeImmutable('2024-05-07');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($date);

        $user = (new User('0de5692b-cab1-494c-804d-765dc14df674'))
            ->setFullName('Mathieu MARCHOIS')
            ->setEmail('mathieu@fairness.coop')
            ->setRegistrationDate($date)
            ->setRoles([UserRolesEnum::ROLE_USER->value]);

        $passwordUser = new PasswordUser('3e4ea113-9f64-4933-a699-0561b8c15622', 'passwordHashed', $user);
        $user->setPasswordUser($passwordUser);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('12345')
            ->willReturn('passwordHashed');

        $this->passwordUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($passwordUser));

        $organizationUser = (new OrganizationUser('f40f95eb-a7dd-4232-9f03-2db10f04f37f'))
            ->setOrganization($organization)
            ->setUser($user)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value);

        $this->organizationUserRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($organizationUser));

        $this->userRepository
            ->expects(self::once())
            ->method('add')
            ->with($this->equalTo($user));

        $this->assertEquals(($this->handler)($this->command), $user);
    }

    public function testRegisterWithBadSiret(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn(null);

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->willThrowException(new OrganizationNotFoundException());

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $this->organizationUserRepository
            ->expects(self::never())
            ->method('add');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        ($this->handler)($this->command);
    }

    public function testUserAlreadyRegistered(): void
    {
        $this->expectException(UserAlreadyRegisteredException::class);

        $user = $this->createMock(User::class);

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn($user);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $this->passwordUserRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->userRepository
            ->expects(self::never())
            ->method('add');

        ($this->handler)($this->command);
    }
}

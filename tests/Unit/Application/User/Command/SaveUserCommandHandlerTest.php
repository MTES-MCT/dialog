<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\User\Command\SaveUserCommand;
use App\Application\User\Command\SaveUserCommandHandler;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Exception\UserAlreadyExistException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class SaveUserCommandHandlerTest extends TestCase
{
    public function testException()
    {
        $this->expectException(UserAlreadyExistException::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $user = $this->createMock(User::class);

        $userRepository
        ->expects(self::once())
        ->method('findOneByEmail')
        ->with('lea.lefoulon@gmail.com')
        ->willReturn($user);

        $userRepository
        ->expects(self::never())
        ->method('save');

        $command = new SaveUserCommand();
        $command->fullName = 'lea';
        $command->email = 'lea.lefoulon@gmail.com';
        $command->password = 'azerty';
        $command->organizations = 'dialog';

        $handler = new SaveUserCommandHandler($userRepository, $idFactory, $passwordHasher, $organizationRepository);
        $handler->__invoke($command);
    }

    public function testCreate()
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $idFactory = $this->createMock(IdFactoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);

        $organization
        ->expects(self::once())
        ->method('addUser')
        ->with($user);

        $organizationRepository
        ->expects(self::once())
        ->method('findByUuid')
        ->with('e0d93630-acf7-4722-81e8-ff7d5fa64b66')
        ->willReturn($organization);

        $userRepository
        ->expects(self::once())
        ->method('findOneByEmail')
        ->with('lea.lefoulon@gmail.com')
        ->willReturn(null);

        $userRepository
        ->expects(self::once())
        ->method('save')
        ->with(new User('fba4914e-eb54-464e-bbc9-92712374fe0a', 'lea', 'lea.lefoulon@gmail.com', 'azertysdfsdf'))
        ->willReturn($user);

        $idFactory
        ->expects(self::once())
        ->method('make')
        ->willReturn('fba4914e-eb54-464e-bbc9-92712374fe0a');

        $passwordHasher
        ->expects(self::once())
        ->method('hash')
        ->willReturn('azertysdfsdf');

        $command = new SaveUserCommand();
        $command->fullName = 'lea';
        $command->email = 'lea.lefoulon@gmail.com';
        $command->password = 'azerty';

        $handler = new SaveUserCommandHandler($userRepository, $idFactory, $passwordHasher, $organizationRepository);
        $handler->__invoke($command);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\SetUserAccountStatusCommand;
use App\Application\User\Command\SetUserAccountStatusCommandHandler;
use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Exception\UserNotFoundException;
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

        $handler = new SetUserAccountStatusCommandHandler($userRepository);
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

        $handler = new SetUserAccountStatusCommandHandler($userRepository);
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

        $handler = new SetUserAccountStatusCommandHandler($userRepository);
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', UserAccountStatusEnum::VALIDATED->value));
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::never())
            ->method('findOneByUuid');

        $handler = new SetUserAccountStatusCommandHandler($userRepository);
        $handler(new SetUserAccountStatusCommand('0de5692b-cab1-494c-804d-765dc14df674', 'not-a-status'));
    }
}

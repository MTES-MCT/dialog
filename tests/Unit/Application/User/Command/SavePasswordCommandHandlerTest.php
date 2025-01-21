<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\PasswordHasherInterface;
use App\Application\User\Command\SavePasswordCommand;
use App\Application\User\Command\SavePasswordCommandHandler;
use App\Domain\User\PasswordUser;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SavePasswordCommandHandlerTest extends TestCase
{
    public function testUpdatePassword()
    {
        $user = $this->createMock(User::class);
        $passwordUser = $this->createMock(PasswordUser::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('password')
            ->willReturn('passwordHashed');

        $user
            ->expects(self::once())
            ->method('getPasswordUser')
            ->willReturn($passwordUser);

        $passwordUser
            ->expects(self::once())
            ->method('setPassword')
            ->with('passwordHashed');

        $handler = new SavePasswordCommandHandler(
            $passwordHasher,
        );
        $command = new SavePasswordCommand($user);
        $command->password = 'password';

        $handler($command);
    }
}

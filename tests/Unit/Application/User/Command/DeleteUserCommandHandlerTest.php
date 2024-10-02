<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\User\Command\DeleteUserCommand;
use App\Application\User\Command\DeleteUserCommandHandler;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class DeleteUserCommandHandlerTest extends TestCase
{
    public function testDeleteUser(): void
    {
        $user = $this->createMock(User::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository
            ->expects(self::once())
            ->method('remove')
            ->with($this->equalTo($user));

        $handler = new DeleteUserCommandHandler($userRepository);
        $command = new DeleteUserCommand($user);
        $handler($command);
    }
}

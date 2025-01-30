<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\PasswordHasherInterface;
use App\Application\User\Command\ResetPasswordCommand;
use App\Application\User\Command\ResetPasswordCommandHandler;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\TokenExpiredException;
use App\Domain\User\Exception\TokenNotFoundException;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Specification\IsTokenExpired;
use App\Domain\User\Token;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ResetPasswordCommandHandlerTest extends TestCase
{
    private MockObject $tokenRepository;
    private MockObject $isTokenExpired;
    private MockObject $passwordHasher;

    public function setUp(): void
    {
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->isTokenExpired = $this->createMock(IsTokenExpired::class);
    }

    public function testResetPassword(): void
    {
        $passwordUser = $this->createMock(PasswordUser::class);
        $user = $this->createMock(User::class);

        $passwordUser
            ->expects(self::once())
            ->method('setPassword')
            ->with('newPasswordHash');
        $user
            ->expects(self::once())
            ->method('getPasswordUser')
            ->willReturn($passwordUser);

        $token = $this->createMock(Token::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::FORGOT_PASSWORD->value)
            ->willReturn($token);

        $this->isTokenExpired
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($token)
            ->willReturn(false);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('newPassword')
            ->willReturn('newPasswordHash');

        $this->tokenRepository
            ->expects(self::once())
            ->method('remove')
            ->with($token);

        $command = new ResetPasswordCommand('myToken');
        $command->password = 'newPassword';
        $handler = new ResetPasswordCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->passwordHasher);

        ($handler)($command);
    }

    public function testTokenExpired(): void
    {
        $this->expectException(TokenExpiredException::class);
        $token = $this->createMock(Token::class);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::FORGOT_PASSWORD->value)
            ->willReturn($token);

        $this->isTokenExpired
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($token)
            ->willReturn(true);

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $command = new ResetPasswordCommand('myToken');
        $command->password = 'newPassword';
        $handler = new ResetPasswordCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->passwordHasher);

        ($handler)($command);
    }

    public function testTokenNotFound(): void
    {
        $this->expectException(TokenNotFoundException::class);
        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::FORGOT_PASSWORD->value)
            ->willReturn(null);

        $this->isTokenExpired
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $this->passwordHasher
            ->expects(self::never())
            ->method('hash');

        $command = new ResetPasswordCommand('myToken');
        $command->password = 'newPassword';
        $handler = new ResetPasswordCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->passwordHasher);

        ($handler)($command);
    }
}

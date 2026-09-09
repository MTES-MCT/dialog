<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\User\Command\ConfirmAccountCommand;
use App\Application\User\Command\ConfirmAccountCommandHandler;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\TokenAlreadyUsedException;
use App\Domain\User\Exception\TokenExpiredException;
use App\Domain\User\Exception\TokenNotFoundException;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Specification\IsTokenExpired;
use App\Domain\User\Token;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfirmAccountCommandHandlerTest extends TestCase
{
    private MockObject $tokenRepository;
    private MockObject $isTokenExpired;
    private MockObject $dateUtils;

    public function setUp(): void
    {
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->isTokenExpired = $this->createMock(IsTokenExpired::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testConfirmUser(): void
    {
        $user = $this->createMock(User::class);
        $user
            ->expects(self::once())
            ->method('setIsVerified');

        $now = new \DateTimeImmutable('2023-06-09 10:00:00');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $token = $this->createMock(Token::class);
        $token
            ->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);
        $token
            ->expects(self::once())
            ->method('isUsed')
            ->willReturn(false);
        $token
            ->expects(self::once())
            ->method('markAsUsed')
            ->with($now);
        $user
            ->expects(self::once())
            ->method('getEmail');

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::CONFIRM_ACCOUNT->value)
            ->willReturn($token);

        $this->tokenRepository
            ->expects(self::never())
            ->method('remove');

        $this->isTokenExpired
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($token)
            ->willReturn(false);

        $command = new ConfirmAccountCommand('myToken');
        $handler = new ConfirmAccountCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->dateUtils);

        ($handler)($command);
    }

    public function testTokenExpired(): void
    {
        $this->expectException(TokenExpiredException::class);
        $token = $this->createMock(Token::class);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::CONFIRM_ACCOUNT->value)
            ->willReturn($token);

        $this->isTokenExpired
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($token)
            ->willReturn(true);

        $command = new ConfirmAccountCommand('myToken');
        $handler = new ConfirmAccountCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->dateUtils);

        ($handler)($command);
    }

    public function testTokenAlreadyUsed(): void
    {
        $this->expectException(TokenAlreadyUsedException::class);
        $token = $this->createMock(Token::class);
        $token
            ->expects(self::once())
            ->method('isUsed')
            ->willReturn(true);
        $token
            ->expects(self::never())
            ->method('markAsUsed');

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::CONFIRM_ACCOUNT->value)
            ->willReturn($token);

        $this->isTokenExpired
            ->expects(self::once())
            ->method('isSatisfiedBy')
            ->with($token)
            ->willReturn(false);

        $command = new ConfirmAccountCommand('myToken');
        $handler = new ConfirmAccountCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->dateUtils);

        ($handler)($command);
    }

    public function testTokenNotFound(): void
    {
        $this->expectException(TokenNotFoundException::class);
        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneByTokenAndType')
            ->with('myToken', TokenTypeEnum::CONFIRM_ACCOUNT->value)
            ->willReturn(null);

        $this->isTokenExpired
            ->expects(self::never())
            ->method('isSatisfiedBy');

        $command = new ConfirmAccountCommand('myToken');
        $handler = new ConfirmAccountCommandHandler($this->tokenRepository, $this->isTokenExpired, $this->dateUtils);

        ($handler)($command);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\User\Command\CreateTokenCommand;
use App\Application\User\Command\CreateTokenCommandHandler;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Token;
use App\Domain\User\TokenGenerator;
use App\Domain\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateTokenCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $userRepository;
    private MockObject $tokenRepository;
    private MockObject $dateUtils;
    private MockObject $tokenGenerator;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
    }

    public function testCreateToken(): void
    {
        $this->tokenGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturn('myToken');

        $expirationDate = new \DateTimeImmutable('2023-08-31 08:00:00');
        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($expirationDate);

        $user = $this->createMock(User::class);
        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn($user);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('ed81607d-476c-4e52-a234-90fddf3ba550');

        $this->tokenRepository
            ->expects(self::once())
            ->method('add')
            ->with(
                new Token(
                    uuid: 'ed81607d-476c-4e52-a234-90fddf3ba550',
                    token: 'myToken',
                    type: TokenTypeEnum::FORGOT_PASSWORD->value,
                    user: $user,
                    expirationDate: new \DateTime('2023-09-01 08:00:00'),
                ),
            );

        $handler = new CreateTokenCommandHandler(
            $this->idFactory,
            $this->userRepository,
            $this->tokenRepository,
            $this->dateUtils,
            $this->tokenGenerator,
        );
        $this->assertSame(
            'myToken',
            ($handler)(new CreateTokenCommand('mathieu@fairness.coop', TokenTypeEnum::FORGOT_PASSWORD->value)),
        );
    }

    public function testUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->tokenGenerator
            ->expects(self::never())
            ->method('generate');

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $this->userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('mathieu@fairness.coop')
            ->willReturn(null);

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->tokenRepository
            ->expects(self::never())
            ->method('add');

        $handler = new CreateTokenCommandHandler(
            $this->idFactory,
            $this->userRepository,
            $this->tokenRepository,
            $this->dateUtils,
            $this->tokenGenerator,
        );

        ($handler)(new CreateTokenCommand('mathieu@fairness.coop', TokenTypeEnum::FORGOT_PASSWORD->value));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Mail;

use App\Application\CommandBusInterface;
use App\Application\MailerInterface;
use App\Application\StringUtilsInterface;
use App\Application\User\Command\CreateTokenCommand;
use App\Application\User\Command\Mail\SendConfirmationMailCommand;
use App\Application\User\Command\Mail\SendConfirmationMailCommandHandler;
use App\Domain\Mail;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Token;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SendConfirmationMailCommandHandlerTest extends TestCase
{
    public function testSendConfirmationLink(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getFullName')->willReturn('Mathieu MARCHOIS');
        $token = $this->createMock(Token::class);
        $token->expects(self::once())->method('getToken')->willReturn('myToken');
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $stringUtils = $this->createMock(StringUtilsInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->with(new CreateTokenCommand('mathieu@fairness.coop', TokenTypeEnum::CONFIRM_ACCOUNT->value))
            ->willReturn($token);

        $mail = $this->createMock(MailerInterface::class);
        $mail
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        address: 'mathieu@fairness.coop',
                        subject: 'confirm_user_account.subject',
                        template: 'email/user/confirm_user_account.html.twig',
                        payload: [
                            'token' => 'myToken',
                            'fullName' => 'Mathieu MARCHOIS',
                        ],
                    ),
                ),
            );

        $stringUtils
            ->expects(self::once())
            ->method('normalizeEmail')
            ->with('   Mathieu@fairness.cooP  ')
            ->willReturn('mathieu@fairness.coop');

        $handler = new SendConfirmationMailCommandHandler($commandBus, $mail, $stringUtils);
        ($handler)(new SendConfirmationMailCommand('   Mathieu@fairness.cooP  '));
    }

    public function testUserNotFound(): void
    {
        $stringUtils = $this->createMock(StringUtilsInterface::class);
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('handle')
            ->willThrowException(new UserNotFoundException());

        $mail = $this->createMock(MailerInterface::class);
        $mail
            ->expects(self::never())
            ->method('send');

        $handler = new SendConfirmationMailCommandHandler($commandBus, $mail, $stringUtils);
        $command = new SendConfirmationMailCommand('   Mathieu@Fairness.coop   ');
        ($handler)($command);
    }
}

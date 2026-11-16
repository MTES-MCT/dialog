<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Application\User\Command\Mail\SendAccountValidatedEmailCommand;
use App\Application\User\Command\Mail\SendAccountValidatedEmailCommandHandler;
use App\Domain\Mail;
use PHPUnit\Framework\TestCase;

final class SendAccountValidatedEmailCommandHandlerTest extends TestCase
{
    public function testSendAccountValidatedEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        from: null,
                        address: 'mathieu@fairness.coop',
                        subject: 'account_validated.subject',
                        template: 'email/user/account_validated.html.twig',
                        payload: [
                            'fullName' => 'Mathieu Marchois',
                        ],
                    ),
                ),
            );

        $handler = new SendAccountValidatedEmailCommandHandler($mailer);
        $command = new SendAccountValidatedEmailCommand('mathieu@fairness.coop', 'Mathieu Marchois');
        ($handler)($command);
    }
}

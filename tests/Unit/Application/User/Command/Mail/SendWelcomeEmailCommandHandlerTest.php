<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Application\User\Command\Mail\SendWelcomeEmailCommand;
use App\Application\User\Command\Mail\SendWelcomeEmailCommandHandler;
use App\Domain\Mail;
use PHPUnit\Framework\TestCase;

final class SendWelcomeEmailCommandHandlerTest extends TestCase
{
    public function testSendWelcomeEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        address: 'mathieu@fairness.coop',
                        subject: 'welcome.email.help.subject',
                        template: 'email/user/welcome_user.html.twig',
                        payload: ['email' => 'mathieu@fairness.coop'],
                    ),
                ),
            );

        $handler = new SendWelcomeEmailCommandHandler($mailer);
        $command = new SendWelcomeEmailCommand('mathieu@fairness.coop');
        ($handler)($command);
    }
}

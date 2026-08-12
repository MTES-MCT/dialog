<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Application\User\Command\Mail\SendInactivityReminderEmailCommand;
use App\Application\User\Command\Mail\SendInactivityReminderEmailCommandHandler;
use App\Domain\Mail;
use PHPUnit\Framework\TestCase;

final class SendInactivityReminderEmailCommandHandlerTest extends TestCase
{
    public function testSendInactivityReminderEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        from: null,
                        address: 'antoine@fairness.coop',
                        subject: 'inactivity.email.subject',
                        template: 'email/user/inactivity_reminder.html.twig',
                    ),
                ),
            );

        $handler = new SendInactivityReminderEmailCommandHandler($mailer);
        $command = new SendInactivityReminderEmailCommand('antoine@fairness.coop');
        ($handler)($command);
    }
}

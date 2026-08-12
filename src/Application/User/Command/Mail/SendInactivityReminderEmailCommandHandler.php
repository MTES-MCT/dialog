<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Domain\Mail;

final readonly class SendInactivityReminderEmailCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendInactivityReminderEmailCommand $command): void
    {
        $this->mailer->send(
            new Mail(
                address: $command->email,
                subject: 'inactivity.email.subject',
                template: 'email/user/inactivity_reminder.html.twig',
                payload: [
                ],
            ),
        );
    }
}

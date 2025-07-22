<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Domain\Mail;

final readonly class SendWelcomeEmailCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendWelcomeEmailCommand $command): void
    {
        $this->mailer->send(
            new Mail(
                address: $command->email,
                subject: 'welcome.email.help.subject',
                template: 'email/user/welcome_user.html.twig',
                payload: [
                    'addressEmail' => $command->email,
                ],
            ),
        );
    }
}

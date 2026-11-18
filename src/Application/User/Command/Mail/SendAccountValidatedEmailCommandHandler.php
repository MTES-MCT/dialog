<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\MailerInterface;
use App\Domain\Mail;

final readonly class SendAccountValidatedEmailCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendAccountValidatedEmailCommand $command): void
    {
        $this->mailer->send(
            new Mail(
                from: null,
                address: $command->email,
                subject: 'account_validated.subject',
                template: 'email/user/account_validated.html.twig',
                payload: [
                    'fullName' => $command->fullName,
                ],
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\MailerInterface;
use App\Infrastructure\Adapter\StringUtils;

final readonly class SendRegulationOrderToMailingListCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private StringUtils $stringUtils,
    ) {
    }

    public function __invoke(SendRegulationOrderToMailingListCommand $command)
    {
        $recipients = $command->emails;
        foreach ($recipients as $recipient) {
            $this->stringUtils->normalizeEmail($recipient);
            $this->mailer->send(
                new Mail(
                    address: $email,
                    subject: '',
                    template: 'email/user/organization_invitation.html.twig',
                    payload: [],
                ),
            );
        }
    }
}

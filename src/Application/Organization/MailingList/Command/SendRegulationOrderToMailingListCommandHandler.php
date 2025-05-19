<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\MailerInterface;
use App\Domain\Mail;
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
        $recipients = [];
        if (!empty($command->emails)) {
            $emailList = explode(',', $command->emails);
            foreach ($emailList as $email) {
                $recipients[] = [
                    'name' => null,
                    'email' => $this->stringUtils->normalizeEmail($email),
                ];
            }
        }
        if (!empty($command->recipients)) {
            $recipientList = $command->recipients;
            foreach ($recipientList as $recipient) {
                [$name, $email] = explode('#', $recipient);
                $recipients[] = [
                    'name' => $name,
                    'email' => $this->stringUtils->normalizeEmail($email),
                ];
            }
        }

        foreach ($recipients as $recipient) {
            $this->mailer->send(
                new Mail(
                    address: $recipient['email'],
                    subject: 'mailing_list.email.subject',
                    template: 'email/mailing_list/mailing_list_email.html.twig',
                    payload: [
                        'recipient' => $recipient,
                        'regulationOrder' => $command->regulationOrder,
                        'userName' => $command->user->getFullName(),
                        'uuid' => $command->regulationOrderRecord->getUuid(),
                    ],
                ),
            );
        }
    }
}

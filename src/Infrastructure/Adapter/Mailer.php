<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\MailerInterface;
use App\Domain\Mail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailer;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class Mailer implements MailerInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private SymfonyMailer $mailer,
        private string $emailSupport,
    ) {
    }

    public function send(Mail $mail): void
    {
        $this->mailer->send(
            (new TemplatedEmail())
                ->from(new Address($mail->from ?? $this->emailSupport))
                ->to(new Address($mail->address))
                ->subject($this->translator->trans($mail->subject, [], 'emails'))
                ->htmlTemplate($mail->template)
                ->context($mail->payload),
        );
    }
}

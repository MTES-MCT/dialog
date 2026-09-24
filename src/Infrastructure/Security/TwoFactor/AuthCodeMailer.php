<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\TwoFactor;

use App\Application\MailerInterface;
use App\Domain\Mail;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

/**
 * Envoie le code de double authentification par e-mail en réutilisant le mailer applicatif
 * (gabarits Twig + traductions du domaine `emails`).
 */
final readonly class AuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();
        if ($authCode === null) {
            return;
        }

        $this->mailer->send(
            new Mail(
                from: null,
                address: $user->getEmailAuthRecipient(),
                subject: 'two_factor_auth_code.subject',
                template: 'email/user/two_factor_auth_code.html.twig',
                payload: [
                    'code' => $authCode,
                ],
            ),
        );
    }
}

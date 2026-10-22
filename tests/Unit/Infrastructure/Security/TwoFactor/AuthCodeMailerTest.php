<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\TwoFactor;

use App\Application\MailerInterface;
use App\Domain\Mail;
use App\Infrastructure\Security\TwoFactor\AuthCodeMailer;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

final class AuthCodeMailerTest extends TestCase
{
    public function testSendAuthCode(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user->expects(self::once())->method('getEmailAuthCode')->willReturn('123456');
        $user->expects(self::once())->method('getEmailAuthRecipient')->willReturn('mathieu@fairness.coop');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(
                $this->equalTo(
                    new Mail(
                        from: null,
                        address: 'mathieu@fairness.coop',
                        subject: 'two_factor_auth_code.subject',
                        template: 'email/user/two_factor_auth_code.html.twig',
                        payload: [
                            'code' => '123456',
                        ],
                    ),
                ),
            );

        (new AuthCodeMailer($mailer))->sendAuthCode($user);
    }

    public function testDoesNotSendWhenNoCode(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user->expects(self::once())->method('getEmailAuthCode')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        (new AuthCodeMailer($mailer))->sendAuthCode($user);
    }
}

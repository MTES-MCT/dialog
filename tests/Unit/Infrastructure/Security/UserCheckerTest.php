<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use App\Infrastructure\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserCheckerTest extends TestCase
{
    public function testCheckPreAuthUserVerified(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::never())
            ->method('trans');

        $user = $this->createMock(AbstractAuthenticatedUser::class);
        $user
            ->expects(self::once())
            ->method('isVerified')
            ->willReturn(true);
        $user
            ->expects(self::once())
            ->method('isValidated')
            ->willReturn(true);

        $checker = new UserChecker($translator);
        $this->assertEmpty($checker->checkPreAuth($user));
    }

    public function testCheckPreAuthUserNotVerified(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Vous devez valider votre compte grâce au mail de confirmation reçu.');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('login.error.not_verified_account')
            ->willReturn('Vous devez valider votre compte grâce au mail de confirmation reçu.');

        $user = $this->createMock(AbstractAuthenticatedUser::class);
        $user
            ->expects(self::once())
            ->method('isVerified')
            ->willReturn(false);

        $checker = new UserChecker($translator);
        $this->assertEmpty($checker->checkPreAuth($user));
    }

    public function testCheckPreAuthUserPendingValidation(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte est en attente de validation par notre équipe.');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('login.error.pending_validation')
            ->willReturn('Votre compte est en attente de validation par notre équipe.');

        $user = $this->createMock(AbstractAuthenticatedUser::class);
        $user
            ->expects(self::once())
            ->method('isVerified')
            ->willReturn(true);
        $user
            ->expects(self::once())
            ->method('isValidated')
            ->willReturn(false);

        $checker = new UserChecker($translator);
        $this->assertEmpty($checker->checkPreAuth($user));
    }
}

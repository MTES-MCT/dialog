<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\User;

use App\Domain\User\User;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class PasswordUser extends AbstractAuthenticatedUser implements PasswordAuthenticatedUserInterface, TwoFactorInterface, TrustedDeviceInterface
{
    private string $password;
    private bool $isVerified;
    private ?string $emailAuthCode;

    public function __construct(
        User $user,
        array $userOrganizations,
        ?\DateTimeInterface $now = null,
    ) {
        parent::__construct($user, $userOrganizations);

        $this->password = $user->getPasswordUser()->getPassword();
        $this->isVerified = $user->isVerified();

        // Un code expiré est considéré comme absent : l'utilisateur devra en demander un nouveau.
        // L'horloge est injectée (via le provider) pour rester cohérente avec la date de génération.
        $now ??= new \DateTimeImmutable();
        $expiresAt = $user->getEmailAuthCodeExpiresAt();
        $this->emailAuthCode = ($expiresAt !== null && $expiresAt < $now) ? null : $user->getEmailAuthCode();
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAuthOrigin(): string
    {
        return 'local';
    }

    public function isEmailAuthEnabled(): bool
    {
        // La double authentification par e-mail est obligatoire pour tous les comptes locaux.
        return true;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): ?string
    {
        return $this->emailAuthCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->emailAuthCode = $authCode;
    }

    public function getTrustedTokenVersion(): int
    {
        return 0;
    }
}

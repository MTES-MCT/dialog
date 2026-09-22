<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\TwoFactor;

use App\Application\DateUtilsInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\User\PasswordUser;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Model\PersisterInterface;

/**
 * Persiste le code de double authentification par e-mail sur l'entité métier User.
 *
 * Remplace le persister Doctrine par défaut de scheb, qui tenterait de persister
 * l'utilisateur de sécurité (PasswordUser), lequel n'est pas une entité Doctrine.
 */
final readonly class EmailAuthCodePersister implements PersisterInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DateUtilsInterface $dateUtils,
        private EntityManagerInterface $entityManager,
        private int $twoFactorCodeLifetimeMinutes,
    ) {
    }

    public function persist(object $user): void
    {
        if (!$user instanceof PasswordUser) {
            return;
        }

        $domainUser = $this->userRepository->findOneByEmail($user->getUserIdentifier());
        if ($domainUser === null) {
            return;
        }

        $domainUser->setEmailAuthCode($user->getEmailAuthCode());
        $domainUser->setEmailAuthCodeExpiresAt(
            $this->dateUtils->getNow()->modify(\sprintf('+%d minutes', $this->twoFactorCodeLifetimeMinutes)),
        );

        $this->entityManager->flush();
    }
}

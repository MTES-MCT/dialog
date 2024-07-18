<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\User\Organization;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Organization) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof SymfonyUser) {
            return false;
        }

        /** @var Organization $organization */
        $organization = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($organization, $user),
            self::EDIT => $this->canEdit($organization, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Organization $organization, SymfonyUser $user): bool
    {
        return \in_array($organization->getUuid(), $user->getOrganizationUuids());
    }

    private function canEdit(Organization $organization, SymfonyUser $user): bool
    {
        return $this->canView($organization, $user);
    }
}

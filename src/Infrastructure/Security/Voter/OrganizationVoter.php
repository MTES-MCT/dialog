<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserEditOrganization;
use App\Domain\User\Specification\CanUserViewOrganization;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrganizationVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    public function __construct(
        private readonly CanUserEditOrganization $canUserEditOrganization,
        private readonly CanUserViewOrganization $canUserViewOrganization,
    ) {
    }

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
            self::VIEW => $this->canUserViewOrganization->isSatisfiedBy($organization, $user),
            self::EDIT => $this->canUserEditOrganization->isSatisfiedBy($organization, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }
}

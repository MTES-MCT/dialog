<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Specification\CanUserPublishRegulation;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class RegulationOrderRecordVoter extends Voter
{
    public function __construct(
        private readonly CanUserPublishRegulation $canUserPublishRegulation,
    ) {
    }

    public const PUBLISH = 'publish';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::PUBLISH])) {
            return false;
        }

        if (!$subject instanceof RegulationOrderRecord) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof AbstractAuthenticatedUser) {
            return false;
        }

        /** @var RegulationOrderRecord $regulationOrderRecord */
        $regulationOrderRecord = $subject;

        return match ($attribute) {
            self::PUBLISH => $this->canUserPublishRegulation->isSatisfiedBy($regulationOrderRecord, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }
}

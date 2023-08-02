<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;

class DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
{
    public function __construct(
        private readonly RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function isSatisfiedBy(string $newIdentifier, Organization $organization): bool
    {
        return $this->regulationOrderRecordRepository->doesOneExistInOrganizationWithIdentifier($organization, $newIdentifier);
    }
}

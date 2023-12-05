<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Domain\User\AccessRequest;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;

final class IsAccessAlreadyRequested
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
    ) {
    }

    public function isSatisfiedBy(string $email): bool
    {
        return $this->accessRequestRepository->findOneByEmail($email) instanceof AccessRequest;
    }
}

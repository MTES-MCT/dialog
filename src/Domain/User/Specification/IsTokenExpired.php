<?php

declare(strict_types=1);

namespace App\Domain\User\Specification;

use App\Application\DateUtilsInterface;
use App\Domain\User\Token;

final class IsTokenExpired
{
    public function __construct(
        private readonly DateUtilsInterface $dateUtils,
    ) {
    }

    public function isSatisfiedBy(Token $token): bool
    {
        return $this->dateUtils->getNow() > $token->getExpirationDate();
    }
}

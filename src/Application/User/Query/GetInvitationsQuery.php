<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\QueryInterface;

final class GetInvitationsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $organizationUuid,
    ) {
    }
}

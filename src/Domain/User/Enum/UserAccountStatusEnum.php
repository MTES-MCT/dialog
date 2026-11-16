<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum UserAccountStatusEnum: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REFUSED = 'refused';
}

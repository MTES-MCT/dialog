<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum TokenTypeEnum: string
{
    case FORGOT_PASSWORD = 'FORGOT_PASSWORD';
    case CONFIRM_ACCOUNT = 'CONFIRM_ACCOUNT';
}

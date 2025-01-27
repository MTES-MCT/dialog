<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum ActionTypeEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case PUBLISH = 'publish';
    case DELETE = 'delete';
}

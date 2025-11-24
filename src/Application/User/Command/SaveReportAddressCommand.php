<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class SaveReportAddressCommand implements CommandInterface
{
    public ?string $content = null;
    public ?string $roadType = null;

    public function __construct(
        public User $user,
    ) {
    }
}

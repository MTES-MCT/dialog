<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

final class SaveFeedbackCommand implements CommandInterface
{
    public ?string $content = null;
    public ?bool $consentToBeContacted = false;

    public function __construct(
        public User $user,
    ) {
    }
}

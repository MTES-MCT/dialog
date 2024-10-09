<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;

final class MarkUserAsActiveCommandHandler
{
    public function __construct(
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(MarkUserAsActiveCommand $command): void
    {
        $command->user
            ->setLastActiveAt($this->dateUtils->getNow());
    }
}

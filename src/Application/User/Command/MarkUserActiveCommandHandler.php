<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;

final class MarkUserActiveCommandHandler
{
    public function __construct(
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(MarkUserActiveCommand $command): void
    {
        $command->user
            ->setLastActiveAt($this->dateUtils->getNow());
    }
}

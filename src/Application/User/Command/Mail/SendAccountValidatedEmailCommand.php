<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\AsyncCommandInterface;

final class SendAccountValidatedEmailCommand implements AsyncCommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $fullName,
    ) {
    }
}

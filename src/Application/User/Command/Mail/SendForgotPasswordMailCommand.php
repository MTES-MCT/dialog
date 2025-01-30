<?php

declare(strict_types=1);

namespace App\Application\User\Command\Mail;

use App\Application\AsyncCommandInterface;

final class SendForgotPasswordMailCommand implements AsyncCommandInterface
{
    public ?string $email = null;
}

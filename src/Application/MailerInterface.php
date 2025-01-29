<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Mail;

interface MailerInterface
{
    public function send(Mail $mail): void;
}

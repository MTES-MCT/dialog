<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;

final class SaveAccessRequestCommand implements CommandInterface
{
    public ?string $fullName;
    public ?string $email;
    public ?string $organization;
    public ?string $siret;
    public ?string $password;
    public ?string $comment = null;
    public ?bool $consentToBeContacted = false;
}

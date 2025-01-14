<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\PasswordUser;

interface PasswordUserRepositoryInterface
{
    public function add(PasswordUser $passwordUser): PasswordUser;
}

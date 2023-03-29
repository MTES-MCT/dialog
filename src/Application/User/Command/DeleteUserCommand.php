<?php

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\User;

class DeleteUserCommand implements CommandInterface
{
    public function __construct(
        public User $user,
    )
    {   
    }
}
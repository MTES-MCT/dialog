<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\User;

interface UserRepositoryInterface
{
    public function findOneByEmail(string $email): ?User;
    public function findUsers(): array;
    public function save(User $user): User;
    public function findUserByUuid(string $uuid): User| null;
    public function delete(User $user):void;
}

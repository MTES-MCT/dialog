<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\AccessRequest;

interface AccessRequestRepositoryInterface
{
    public function add(AccessRequest $accessrequest): AccessRequest;

    public function findOneByEmail(string $email): ?AccessRequest;

    public function findOneByUuid(string $uuid): ?AccessRequest;

    public function remove(AccessRequest $accessrequest): void;
}

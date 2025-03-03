<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\ProConnectUser;

interface ProConnectUserRepositoryInterface
{
    public function add(ProConnectUser $proConnectUser): ProConnectUser;
}

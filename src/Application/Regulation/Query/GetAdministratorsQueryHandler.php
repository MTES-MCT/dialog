<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\AdministratorListInterface;

final class GetAdministratorsQueryHandler
{
    public function __construct(
        private AdministratorListInterface $administratorList,
    ) {
    }

    public function __invoke(GetAdministratorsQuery $query): array
    {
        return $this->administratorList->findAll();
    }
}

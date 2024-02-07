<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\AdministratorRepositoryInterface;

final class GetAdministratorsQueryHandler
{
    public function __construct(
        private AdministratorRepositoryInterface $administratorRepository,
    ) {
    }

    public function __invoke(GetAdministratorsQuery $query): array
    {
        return $this->administratorRepository->findAll();
    }
}

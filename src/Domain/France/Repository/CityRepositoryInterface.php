<?php

declare(strict_types=1);

namespace App\Domain\France\Repository;

use App\Domain\France\City;

interface CityRepositoryInterface
{
    public function findOneByNameAndDepartement(string $name, string $departement): ?City;
}

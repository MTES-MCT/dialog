<?php

declare(strict_types=1);

namespace App\Domain\Country\France\Repository;

use App\Domain\Country\France\City;

interface CityRepositoryInterface
{
    public function findOneByNameAndDepartement(string $name, string $departement): ?City;
}

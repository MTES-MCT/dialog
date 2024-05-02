<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\NamedStreet;

interface NamedStreetRepositoryInterface
{
    public function add(NamedStreet $namedStreet): NamedStreet;

    public function delete(NamedStreet $namedStreet): void;
}

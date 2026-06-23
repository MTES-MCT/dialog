<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\WholeCity;

interface WholeCityRepositoryInterface
{
    public function add(WholeCity $wholeCity): WholeCity;

    public function delete(WholeCity $wholeCity): void;
}

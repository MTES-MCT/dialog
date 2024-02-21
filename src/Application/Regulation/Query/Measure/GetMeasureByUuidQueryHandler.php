<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Measure;

use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class GetMeasureByUuidQueryHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    public function __invoke(GetMeasureByUuidQuery $query): ?Measure
    {
        return $this->measureRepository->findOneByUuid($query->uuid);
    }
}

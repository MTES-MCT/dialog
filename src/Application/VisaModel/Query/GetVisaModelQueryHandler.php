<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Query;

use App\Domain\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;

final class GetVisaModelQueryHandler
{
    public function __construct(
        private VisaModelRepositoryInterface $visaModelRepository,
    ) {
    }

    public function __invoke(GetVisaModelQuery $query): VisaModel
    {
        $visaModel = $this->visaModelRepository->findOneByUuid($query->uuid);

        if (!$visaModel instanceof VisaModel) {
            throw new VisaModelNotFoundException();
        }

        return $visaModel;
    }
}

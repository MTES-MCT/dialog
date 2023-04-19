<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationForLocationView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationForLocationQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationForLocationQuery $query): RegulationForLocationView
    {
        $row = $this->regulationOrderRecordRepository->findOneForLocationByUuid(
            $query->uuid,
        );

        if (!$row) {
            throw new RegulationOrderRecordNotFoundException();
        }

        return new RegulationForLocationView(
            $row['uuid'],
            $row['organizationUuid'],
            $row['regulationOrderUuid'],
            $row['status'],
        );
    }
}

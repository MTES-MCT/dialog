<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationGeneralInfoView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationGeneralInfoQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationGeneralInfoQuery $query): RegulationGeneralInfoView
    {
        $row = $this->regulationOrderRecordRepository->findOneGeneralInfoByUuid(
            $query->uuid,
        );

        if (!$row) {
            throw new RegulationOrderRecordNotFoundException();
        }

        return new RegulationGeneralInfoView(
            $row['uuid'],
            $row['organizationUuid'],
            $row['organizationName'],
            $row['status'],
            $row['description'],
            $row['startDate'],
            $row['endDate'],
        );
    }
}

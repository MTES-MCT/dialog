<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetGeneralInfoQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetGeneralInfoQuery $query): GeneralInfoView
    {
        $row = $this->regulationOrderRecordRepository->findGeneralInformation(
            $query->uuid,
        );

        if (!$row) {
            throw new RegulationOrderRecordNotFoundException();
        }

        return new GeneralInfoView(
            uuid: $row['uuid'],
            identifier: $row['identifier'],
            organizationName: $row['organizationName'],
            organizationLogo: $row['organizationLogo'],
            organizationUuid: $row['organizationUuid'],
            status: $row['status'],
            regulationOrderUuid: $row['regulationOrderUuid'],
            category: $row['category'],
            otherCategoryText: $row['otherCategoryText'],
            description: $row['description'],
            startDate: $row['overallStartDate'] ? new \DateTimeImmutable($row['overallStartDate']) : null,
            endDate: $row['overallEndDate'] ? new \DateTimeImmutable($row['overallEndDate']) : null,
        );
    }
}

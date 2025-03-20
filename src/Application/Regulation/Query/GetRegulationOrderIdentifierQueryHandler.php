<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrderIdentifierQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(GetRegulationOrderIdentifierQuery $query): string
    {
        $nextNumber = $this->regulationOrderRecordRepository->countRegulationOrderRecordsForOrganizationDuringCurrentMonth($query->userUuid);
        $prefix = $this->dateUtils->getNow()->format('Y-m');

        return \sprintf('%s-%s', $prefix, str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT));
    }
}

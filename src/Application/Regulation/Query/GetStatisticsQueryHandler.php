<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\StatisticsView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetStatisticsQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetStatisticsQuery $query): StatisticsView
    {
        return new StatisticsView(
            totalRegulationOrderRecords: $this->regulationOrderRecordRepository->countTotalRegulationOrderRecords(),
            publishedRegulationOrderRecords: $this->regulationOrderRecordRepository->countPublishedRegulationOrderRecords(),
            permanentRegulationOrderRecords: $this->regulationOrderRecordRepository->countPermanentRegulationOrderRecords(),
            temporaryRegulationOrderRecords: $this->regulationOrderRecordRepository->countTemporaryRegulationOrderRecords(),
        );
    }
}

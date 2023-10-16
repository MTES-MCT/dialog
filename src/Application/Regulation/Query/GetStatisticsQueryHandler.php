<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\StatisticsView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final class GetStatisticsQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetStatisticsQuery $query): StatisticsView
    {
        return new StatisticsView(
            users: $this->userRepository->countUsers(),
            organizations: $this->organizationRepository->countOrganizations(),
            totalRegulationOrderRecords: $this->regulationOrderRecordRepository->countTotalRegulationOrderRecords(),
            publishedRegulationOrderRecords: $this->regulationOrderRecordRepository->countPublishedRegulationOrderRecords(),
            permanentRegulationOrderRecords: $this->regulationOrderRecordRepository->countPermanentRegulationOrderRecords(),
            temporaryRegulationOrderRecords: $this->regulationOrderRecordRepository->countTemporaryRegulationOrderRecords(),
        );
    }
}

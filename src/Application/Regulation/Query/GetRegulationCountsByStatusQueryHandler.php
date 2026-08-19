<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\View\RegulationCountsByStatusView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationCountsByStatusQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(GetRegulationCountsByStatusQuery $query): RegulationCountsByStatusView
    {
        $counts = $this->repository->countRegulationsByStatusForOrganizations(
            $query->organizationUuids,
            $this->dateUtils->getNow(),
        );

        return new RegulationCountsByStatusView(
            draftCount: $counts['draftCount'],
            currentCount: $counts['currentCount'],
            upcomingCount: $counts['upcomingCount'],
        );
    }
}

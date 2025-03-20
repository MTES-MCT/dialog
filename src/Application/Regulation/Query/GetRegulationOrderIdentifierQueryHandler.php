<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetRegulationOrderIdentifierQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(GetRegulationOrderIdentifierQuery $query): string
    {
        $nextNumber = $this->regulationOrderRepository->countRegulationOrdersForOrganizationDuringCurrentMonth($query->userUuid);
        $prefix = $this->dateUtils->getNow()->format('Y-m');

        return \sprintf('%s-%s', $prefix, str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT));
    }
}

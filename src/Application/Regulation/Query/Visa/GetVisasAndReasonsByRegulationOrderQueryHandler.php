<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Visa;

use App\Application\Regulation\View\VisasAndReasonsView;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class GetVisasAndReasonsByRegulationOrderQueryHandler
{
    public function __construct(
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
    ) {
    }

    public function __invoke(GetVisasAndReasonsByRegulationOrderQuery $query): VisasAndReasonsView
    {
        $visasAndReasons = $this->regulationOrderRepository->findVisasAndReasonsByRegulationOrderUuid($query->regulationOrderUuid);

        if (!$visasAndReasons) {
            return new VisasAndReasonsView();
        }

        return new VisasAndReasonsView(
            array_merge($visasAndReasons['visas'] ?? [], $visasAndReasons['additionalVisas'] ?? []),
            $visasAndReasons['additionalReasons'] ?? [],
        );
    }
}

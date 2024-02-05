<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\RegulationOrderLocationsView;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class GetRegulationLocationsQueryHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    public function __invoke(GetRegulationLocationsQuery $query): RegulationOrderLocationsView
    {
        $measures = $this->measureRepository->findByRegulationOrderRecordUuid(
            $query->uuid,
        );

        $locationViews = [];

        foreach ($measures as $measure) {
            $locationViews[] = MeasureView::fromEntity($measure);
        }

        return new RegulationOrderLocationsView($locationViews);
    }
}

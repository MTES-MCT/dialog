<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Measure;

use App\Application\Regulation\PeriodPublicHolidaysProvider;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class GetMeasuresQueryHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
        private PeriodPublicHolidaysProvider $publicHolidaysProvider,
    ) {
    }

    public function __invoke(GetMeasuresQuery $query): array
    {
        $measures = $this->measureRepository->findByRegulationOrderRecordUuid(
            $query->uuid,
        );

        $measureViews = [];

        foreach ($measures as $measure) {
            $measureViews[] = MeasureView::fromEntity($measure, $this->publicHolidaysProvider);
        }

        return $measureViews;
    }
}

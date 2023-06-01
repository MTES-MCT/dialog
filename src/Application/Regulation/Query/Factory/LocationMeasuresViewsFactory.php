<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Factory;

use App\Application\Regulation\View\MeasureView;
use App\Application\Regulation\View\PeriodView;
use App\Domain\Regulation\Location;

final class LocationMeasuresViewsFactory
{
    public static function create(Location $location): array
    {
        $measureViews = [];

        foreach ($location->getMeasures() as $measure) {
            $periods = [];

            foreach ($measure->getPeriods() as $period) {
                $periods[] = new PeriodView(
                    $period->getDaysRanges(),
                    $period->getStartTime(),
                    $period->getEndTime(),
                );
            }

            $measureViews[] = new MeasureView($measure->getType(), $periods);
        }

        return $measureViews;
    }
}

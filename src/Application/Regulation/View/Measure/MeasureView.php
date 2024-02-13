<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

use App\Application\Regulation\View\DailyRangeView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\TimeSlotView;
use App\Application\Regulation\View\VehicleSetView;
use App\Domain\Regulation\Measure;

readonly class MeasureView
{
    public function __construct(
        public string $uuid,
        public string $type,
        public ?iterable $periods = null,
        public ?VehicleSetView $vehicleSet = null,
        public ?int $maxSpeed = null,
        public array $locations = [],
    ) {
    }

    public static function fromEntity(Measure $measure): self
    {
        $periods = [];
        $locations = [];

        foreach ($measure->getPeriods() as $period) {
            $dailyRange = $period->getDailyRange();
            $dailyRangeView = $dailyRange ? new DailyRangeView($dailyRange->getDaysRanges()) : null;
            $timeSlotsViews = [];

            foreach ($period->getTimeSlots() as $timeSlot) {
                $timeSlotsViews[] = new TimeSlotView(
                    $timeSlot->getStartTime(),
                    $timeSlot->getEndTime(),
                );
            }

            $periods[] = new PeriodView(
                $period->getRecurrenceType(),
                $period->getStartDateTime(),
                $period->getEndDateTime(),
                $dailyRangeView,
                $timeSlotsViews,
            );
        }

        foreach ($measure->getLocationsNew() as $location) {
            $locations[] = new LocationView(
                cityLabel: $location->getCityLabel(),
                roadName: $location->getRoadName(),
                roadType: $location->getRoadType(),
                fromHouseNumber: $location->getFromHouseNumber(),
                toHouseNumber: $location->getToHouseNumber(),
            );
        }

        return new MeasureView(
            $measure->getUuid(),
            $measure->getType(),
            $periods,
            VehicleSetView::fromEntity($measure->getVehicleSet()),
            $measure->getMaxSpeed(),
            $locations,
        );
    }
}

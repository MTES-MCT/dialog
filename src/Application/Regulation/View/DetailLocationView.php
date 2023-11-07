<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\LocationAddress;

class DetailLocationView
{
    public function __construct(
        public readonly string $uuid,
        public readonly LocationAddress $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
        public array $measures,
    ) {
    }

    public static function fromEntity(Location $location): self
    {
        $measures = [];

        foreach ($location->getMeasures() as $measure) {
            $periods = [];

            foreach ($measure->getPeriods() as $period) {
                $dailyRange = $period->getDailyRange();
                $dailyRangeView = null;

                if ($dailyRange) {
                    $timeSlotsViews = [];

                    foreach ($dailyRange->getTimeSlots() as $timeSlot) {
                        $timeSlotsViews[] = new TimeSlotView(
                            $timeSlot->getStartTime(),
                            $timeSlot->getEndTime(),
                        );
                    }
                    $dailyRangeView = new DailyRangeView($dailyRange->getDaysRanges(), $timeSlotsViews);
                }

                $periods[] = new PeriodView(
                    $period->getRecurrenceType(),
                    $period->getStartDateTime(),
                    $period->getEndDateTime(),
                    $dailyRangeView,
                );
            }

            $measures[] = new MeasureView(
                $measure->getType(),
                $periods,
                VehicleSetView::fromEntity($measure->getVehicleSet()),
                $measure->getMaxSpeed(),
            );
        }

        return new self(
            uuid: $location->getUuid(),
            address: LocationAddress::fromString($location->getAddress()),
            fromHouseNumber: $location->getFromHouseNumber(),
            toHouseNumber: $location->getToHouseNumber(),
            measures: $measures,
        );
    }
}

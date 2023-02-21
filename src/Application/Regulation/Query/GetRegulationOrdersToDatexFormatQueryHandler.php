<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Application\Regulation\View\VehicleCharacteristicsView;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersToDatexFormatQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetRegulationOrdersToDatexFormatQuery $query): array
    {
        $regulationOrders = $this->repository->findRegulationOrdersForDatexFormat();
        $regulationOrderViews = [];

        foreach ($regulationOrders as $regulationOrder) {
            $hasVehicleCharacteristics = $regulationOrder['maxWeight']
                || $regulationOrder['maxHeight']
                || $regulationOrder['maxLength']
                || $regulationOrder['maxWidth'];

            $regulationOrderViews[] = new RegulationOrderDatexListItemView(
                $regulationOrder['uuid'],
                $regulationOrder['issuingAuthority'],
                $regulationOrder['description'],
                new PeriodView(
                    $regulationOrder['startPeriod'],
                    $regulationOrder['endPeriod'],
                ),
                new DatexLocationView(
                    postalCode: $regulationOrder['postalCode'],
                    city: $regulationOrder['city'],
                    roadName: $regulationOrder['roadName'],
                    fromHouseNumber: $regulationOrder['fromHouseNumber'],
                    fromLongitude: $regulationOrder['fromLongitude'],
                    fromLatitude: $regulationOrder['fromLatitude'],
                    toHouseNumber: $regulationOrder['toHouseNumber'],
                    toLongitude: $regulationOrder['toLongitude'],
                    toLatitude: $regulationOrder['toLatitude'],
                ),
                $hasVehicleCharacteristics ? new VehicleCharacteristicsView(
                    $regulationOrder['maxWeight'],
                    $regulationOrder['maxHeight'],
                    $regulationOrder['maxWidth'],
                    $regulationOrder['maxLength'],
                ) : null,
            );
        }

        return $regulationOrderViews;
    }
}

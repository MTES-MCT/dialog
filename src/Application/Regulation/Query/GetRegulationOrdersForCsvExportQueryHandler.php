<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\View\RegulationCsvRowView;
use App\Application\StorageInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;

final class GetRegulationOrdersForCsvExportQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private StorageRegulationOrderRepositoryInterface $storageRegulationOrderRepository,
        private StorageInterface $storage,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    /**
     * @return RegulationCsvRowView[]
     */
    public function __invoke(GetRegulationOrdersForCsvExportQuery $query): array
    {
        $uuids = $this->regulationOrderRecordRepository->findUuidsForApi(
            vigueurStatus: $query->vigueurStatus,
            inseeCode: $query->inseeCode,
            dateStart: $query->dateStart,
            dateEnd: $query->dateEnd,
            category: $query->category,
            measureType: $query->measureType,
            now: $this->dateUtils->getNow(),
        );

        if ($uuids === []) {
            return [];
        }

        $overallDates = $this->regulationOrderRecordRepository->getOverallDatesByRegulationUuids($uuids);
        $pdfInfoByRegulationOrder = $this->storageRegulationOrderRepository->findPdfInfoByRegulationOrderUuids($uuids);

        $rows = [];

        /** @var RegulationOrderRecord $record */
        foreach ($this->regulationOrderRecordRepository->iterateRegulationOrdersForApiByUuids($uuids) as $record) {
            // Le filtre poids lourds s'appuie sur les types restreints, stockés en tableau
            // sérialisé : il ne peut donc pas être appliqué de manière fiable en SQL.
            if (!$query->includeHeavyGoodsVehicle && $this->restrictsHeavyGoodsVehicle($record)) {
                continue;
            }

            $regulationOrder = $record->getRegulationOrder();
            $dates = $overallDates[$record->getUuid()] ?? ['overallStartDate' => null, 'overallEndDate' => null];
            $linkPdf = $this->resolveLinkPdf($pdfInfoByRegulationOrder[$regulationOrder->getUuid()] ?? null);

            /** @var Measure $measure */
            foreach ($regulationOrder->getMeasures() as $measure) {
                foreach ($measure->getLocations() as $location) {
                    $rows[] = new RegulationCsvRowView(
                        regulationOrderUuid: $record->getUuid(),
                        title: $regulationOrder->getTitle(),
                        category: $regulationOrder->getCategory(),
                        status: $record->getStatus(),
                        startDate: $dates['overallStartDate'],
                        endDate: $dates['overallEndDate'],
                        organizationName: $record->getOrganizationName() ?? '',
                        linkPdf: $linkPdf,
                        measureUuid: $measure->getUuid(),
                        measureType: $measure->getType(),
                        locationUuid: $location->getUuid(),
                        locationType: $location->getRoadType(),
                        locationLabel: $this->buildLocationLabel($location),
                    );
                }
            }
        }

        return $rows;
    }

    private function restrictsHeavyGoodsVehicle(RegulationOrderRecord $record): bool
    {
        /** @var Measure $measure */
        foreach ($record->getRegulationOrder()->getMeasures() as $measure) {
            $vehicleSet = $measure->getVehicleSet();

            if ($vehicleSet && \in_array(VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, $vehicleSet->getRestrictedTypes(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{path: ?string, url: ?string}|null $pdfInfo
     */
    private function resolveLinkPdf(?array $pdfInfo): string
    {
        if ($pdfInfo === null) {
            return '';
        }

        // Un document source hébergé chez un tiers (URL) prime sur le fichier stocké par DiaLog.
        if (!empty($pdfInfo['url'])) {
            return $pdfInfo['url'];
        }

        if (!empty($pdfInfo['path'])) {
            return $this->storage->getUrl($pdfInfo['path']);
        }

        return '';
    }

    private function buildLocationLabel(Location $location): string
    {
        if ($namedStreet = $location->getNamedStreet()) {
            return trim(implode(', ', array_filter([
                $namedStreet->getRoadName(),
                $namedStreet->getCityLabel(),
            ])));
        }

        if ($numberedRoad = $location->getNumberedRoad()) {
            $roadNumber = $numberedRoad->getRoadNumber() ?? '';
            $administrator = $numberedRoad->getAdministrator();

            return $administrator ? trim(\sprintf('%s (%s)', $roadNumber, $administrator)) : trim($roadNumber);
        }

        if ($zone = $location->getZone()) {
            return $zone->getLabel();
        }

        if ($rawGeoJSON = $location->getRawGeoJSON()) {
            return $rawGeoJSON->getLabel();
        }

        if ($location->getRoadType() === RoadTypeEnum::WHOLE_CITY->value) {
            return $location->getCityLabel() ?? '';
        }

        return '';
    }
}

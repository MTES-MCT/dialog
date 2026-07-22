<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\RegulationOrderForApiView;
use App\Domain\Pagination;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrdersForApiQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $repository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(GetRegulationOrdersForApiQuery $query): Pagination
    {
        $uuids = $this->repository->findUuidsForApi(
            organization: $query->organization,
            vigueurStatus: $query->vigueurStatus,
            inseeCode: $query->inseeCode,
            dateStart: $query->dateStart,
            dateEnd: $query->dateEnd,
            category: $query->category,
            measureType: $query->measureType,
            now: $this->dateUtils->getNow(),
        );

        if ($uuids === []) {
            return new Pagination([], 0, $query->page, $query->pageSize);
        }

        $overallDates = $this->repository->getOverallDatesByRegulationUuids($uuids);

        $views = [];

        /** @var RegulationOrderRecord $record */
        foreach ($this->repository->iterateRegulationOrdersForApiByUuids($uuids) as $record) {
            // Le filtre poids lourds s'appuie sur les types restreints, stockés en tableau
            // sérialisé : il ne peut donc pas être appliqué de manière fiable en SQL.
            if (!$query->includeHeavyGoodsVehicle && $this->restrictsHeavyGoodsVehicle($record)) {
                continue;
            }

            $views[] = $this->buildView($record, $overallDates);
        }

        $totalItems = \count($views);
        $offset = ($query->page - 1) * $query->pageSize;
        $items = \array_slice($views, $offset, $query->pageSize);

        return new Pagination($items, $totalItems, $query->page, $query->pageSize);
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

    private function buildView(RegulationOrderRecord $record, array $overallDates): RegulationOrderForApiView
    {
        $regulationOrder = $record->getRegulationOrder();
        $dates = $overallDates[$record->getUuid()] ?? ['overallStartDate' => null, 'overallEndDate' => null];

        $measures = [];

        foreach ($regulationOrder->getMeasures() as $measure) {
            $measures[] = MeasureView::fromEntity($measure);
        }

        return new RegulationOrderForApiView(
            identifier: $regulationOrder->getIdentifier(),
            status: $record->getStatus(),
            category: $regulationOrder->getCategory(),
            subject: $regulationOrder->getSubject(),
            otherCategoryText: $regulationOrder->getOtherCategoryText(),
            title: $regulationOrder->getTitle(),
            startDate: $dates['overallStartDate'],
            endDate: $dates['overallEndDate'],
            organizationUuid: $record->getOrganizationUuid(),
            organizationName: $record->getOrganizationName(),
            measures: $measures,
        );
    }
}

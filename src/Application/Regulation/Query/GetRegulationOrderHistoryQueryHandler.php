<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;

final class GetRegulationOrderHistoryQueryHandler
{
    public function __construct(
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderHistoryQuery $query): array
    {
        $rows = $this->regulationOrderHistoryRepository->findLastRegulationOrderHistoriesByRegulationOrderUuid($query->regulationOrderUuid);

        foreach ($rows['items'] as $row) {
            if ($row['action'] === ActionTypeEnum::CREATE->value) {
                $createdAt = $row['date'];
            } elseif ($row['action'] === ActionTypeEnum::UPDATE->value) {
                $updateAt = $row['date'];
            } elseif ($row['action'] === ActionTypeEnum::PUBLISH->value) {
                $publishedAt = $row['date']; //  new \DateTimeImmutable($row['date'])
            }
        }

        $regulationOrderHistory = new RegulationOrderHistoryView(
            createdAt: $createdAt,
            updatedAt: $updateAt,
            publishedAt: $publishedAt,
        );

        return $regulationOrderHistory;
    }
}

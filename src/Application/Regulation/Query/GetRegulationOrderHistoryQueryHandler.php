<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final class GetRegulationOrderHistoryQueryHandler
{
    public function __construct(
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderHistoryQuery $query): ?RegulationOrderHistoryView
    {
        $row = $this->regulationOrderHistoryRepository->findLastRegulationOrderHistoryByUuid($query->regulationOrderUuid);

        if (!$row) {
            return null;
        }

        $user = $row['userUuid'] ? $this->userRepository->findOneByUuid($row['userUuid']) : null;

        return new RegulationOrderHistoryView(
            date: $row['date'],
            action: $row['action'],
            userFullName: $user?->getFullName(),
        );
    }
}

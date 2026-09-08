<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\Regulation\View\RegulationOrderHistoryView;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;

final class GetRegulationOrderHistoryQueryHandler
{
    public function __construct(
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
        private UserRepositoryInterface $userRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderHistoryQuery $query): ?RegulationOrderHistoryView
    {
        $row = $this->regulationOrderHistoryRepository->findLastRegulationOrderHistoryByUuid($query->regulationOrderUuid);

        if (!$row) {
            return null;
        }

        $user = $row['userUuid'] ? $this->userRepository->findOneByUuid($row['userUuid']) : null;

        $isMandataire = false;
        if ($user && $query->organizationUuid) {
            $organizationUser = $this->organizationUserRepository->findOrganizationUser($query->organizationUuid, $user->getUuid());
            $isMandataire = $organizationUser?->isMandataire() ?? false;
        }

        return new RegulationOrderHistoryView(
            date: $row['date'],
            action: $row['action'],
            userFullName: $user?->getFullName(),
            isMandataire: $isMandataire,
        );
    }
}

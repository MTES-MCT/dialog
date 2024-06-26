<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;

final class GetRegulationOrderRecordOrganizationUuidQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderRecordOrganizationUuidQuery $query): string
    {
        $uuid = $this->regulationOrderRecordRepository->findOrganizationUuid(
            $query->uuid,
        );

        if (!$uuid) {
            throw new OrganizationNotFoundException();
        }

        return $uuid;
    }
}

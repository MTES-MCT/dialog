<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;

final class GetOrganizationUuidByRegulationOrderRecordQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetOrganizationUuidByRegulationOrderRecordQuery $query): string
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

<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RejectedLocation;

interface RejectedLocationRepositoryInterface
{
    public function add(RejectedLocation $rejectedLocation): RejectedLocation;

    public function deleteByRegulationOrderRecordUuid(string $regulationOrderRecordUuid): void;

    /** @return RejectedLocation[] */
    public function findByRegulationOrderRecordUuid(string $regulationOrderRecordUuid): array;

    /** @return RejectedLocation[] */
    public function findByOrganizationUuid(string $organizationUuid): array;
}

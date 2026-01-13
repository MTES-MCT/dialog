<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Domain\Regulation\Exception\RegulationOrderRecordNotFoundException;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class GetRegulationOrderRecordByIdentifierQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(GetRegulationOrderRecordByIdentifierQuery $query): RegulationOrderRecord
    {
        $regulationOrderRecord = $this->regulationOrderRecordRepository->findOneByIdentifierInOrganization(
            $query->identifier,
            $query->organization,
        );

        if (!$regulationOrderRecord instanceof RegulationOrderRecord) {
            throw new RegulationOrderRecordNotFoundException();
        }

        return $regulationOrderRecord;
    }
}

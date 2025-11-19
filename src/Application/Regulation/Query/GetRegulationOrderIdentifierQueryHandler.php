<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetRegulationOrderIdentifierQueryHandler
{
    public function __construct(
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private DateUtilsInterface $dateUtils,
        private OrganizationRepositoryInterface $organizationRepository,
        private GetOrganizationIdentifiersQueryHandler $getOrganizationIdentifiersQueryHandler,
    ) {
    }

    public function __invoke(GetRegulationOrderIdentifierQuery $query): string
    {
        $nextNumber = $this->regulationOrderRecordRepository
            ->countRegulationOrderRecordsForOrganizationDuringCurrentMonth($query->uuid);

        $nextIdentifier = $this->getNextIdentifier($nextNumber);

        $organization = $this->organizationRepository->findOneByUuid($query->uuid);
        if (!$organization) {
            throw new OrganizationNotFoundException('Organization not found');
        }

        $identifiers = ($this->getOrganizationIdentifiersQueryHandler)(new GetOrganizationIdentifiersQuery($organization));

        while (\in_array($nextIdentifier, $identifiers)) {
            ++$nextNumber;
            $nextIdentifier = $this->getNextIdentifier($nextNumber);
        }

        return $nextIdentifier;
    }

    private function getNextIdentifier(int $nextNumber): string
    {
        return \sprintf(
            '%s-%s',
            $this->dateUtils->getNow()->format('Y-m'),
            str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
        );
    }
}

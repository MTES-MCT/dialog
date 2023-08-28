<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;

final class EudonetParisExecutor
{
    public function __construct(
        private EudonetParisExtractor $eudonetParisExtractor,
        private EudonetParisTransformer $eudonetParisTransformer,
        private EudonetParisLoader $eudonetParisLoader,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function execute(\DateTimeInterface $laterThanUTC): EudonetParisExecutionReport
    {
        // TODO make it configurable
        $dialogOrgUuid = 'e0d93630-acf7-4722-81e8-ff7d5fa64b66';

        $reportBuilder = new EudonetParisExecutionReportBuilder();

        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($dialogOrgUuid));
        } catch (OrganizationNotFoundException $exc) {
            $reportBuilder->setError((string) $exc);

            return $reportBuilder->build();
        }

        $existingIdentifiers = $this->regulationOrderRecordRepository
            ->findIdentifiersForSourceInOrganization(RegulationOrderRecordSourceEnum::EUDONET_PARIS, $organization);

        foreach ($this->eudonetParisExtractor->iterExtract($laterThanUTC, ignoreIDs: $existingIdentifiers) as $record) {
            $result = $this->eudonetParisTransformer->transform($record, $organization);

            if (empty($result->obj)) {
                $reportBuilder->addSkip($result->skipMessages);
            } else {
                $this->eudonetParisLoader->load($result->obj);
                $reportBuilder->addCreated();
            }
        }

        return $reportBuilder->build();
    }
}

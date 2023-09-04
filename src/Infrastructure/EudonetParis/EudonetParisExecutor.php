<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;

final class EudonetParisExecutor
{
    public function __construct(
        private EudonetParisExtractor $eudonetParisExtractor,
        private EudonetParisTransformer $eudonetParisTransformer,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private string $eudonetParisOrgId,
    ) {
    }

    public function execute(\DateTimeInterface $laterThanUTC): EudonetParisExecutionReport
    {
        if (!$this->eudonetParisOrgId) {
            throw new EudonetParisException('No target organization ID set. Please set APP_EUDONET_PARIS_ORG_ID in .env.local');
        }

        $reportBuilder = new EudonetParisExecutionReportBuilder();

        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->eudonetParisOrgId));
        } catch (OrganizationNotFoundException $exc) {
            $reportBuilder->setError((string) $exc);

            return $reportBuilder->build();
        }

        $existingIdentifiers = $this->regulationOrderRecordRepository
            ->findIdentifiersForSourceInOrganization(RegulationOrderRecordSourceEnum::EUDONET_PARIS->value, $organization);

        foreach ($this->eudonetParisExtractor->iterExtract($laterThanUTC, ignoreIDs: $existingIdentifiers) as $record) {
            $result = $this->eudonetParisTransformer->transform($record, $organization);

            if (empty($result->command)) {
                $reportBuilder->addSkip($result->skipMessages);
            } else {
                $this->commandBus->handle($result->command);
                $reportBuilder->addCreated();
            }
        }

        return $reportBuilder->build();
    }
}

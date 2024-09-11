<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class EudonetParisExecutor
{
    public function __construct(
        private EudonetParisExtractor $eudonetParisExtractor,
        private EudonetParisTransformer $eudonetParisTransformer,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private string $eudonetParisOrgId,
        private EudonetParisReporterFactory $reporterFactory,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function execute(\DateTimeInterface $laterThanUTC): void
    {
        if (!$this->eudonetParisOrgId) {
            throw new EudonetParisException('No target organization ID set. Please set APP_EUDONET_PARIS_ORG_ID in .env.local');
        }

        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->eudonetParisOrgId));
        } catch (OrganizationNotFoundException $exc) {
            throw new EudonetParisException("Organization not found: $this->eudonetParisOrgId");
        }

        $numberOfRegulationsInsideEudonet = null;
        $numberOfMeasuresInsideEudonet = null;

        $startTime = $this->dateUtils->getNow();
        $reporter = $this->reporterFactory->createReporter();
        $reporter->start($startTime, $organization);

        try {
            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSource(RegulationOrderRecordSourceEnum::EUDONET_PARIS->value);

            foreach ($this->eudonetParisExtractor->iterExtract($laterThanUTC, ignoreIDs: $existingIdentifiers) as $record) {
                $command = $this->eudonetParisTransformer->transform($record, $organization, $reporter);

                if ($command === null) { // errors may have occurred, already logged
                    $reporter->acknowledgeNewErrors(); // we go to the next feature -> we reset the reporter's _hasNewErrors attribute
                    continue;
                }
                try {
                    $this->commandBus->handle($command);
                } catch (\Exception $exc) {
                    $reporter->addError($reporter::ERROR_IMPORT_COMMAND_FAILED, [
                        'message' => $exc->getMessage(),
                        'violations' => $exc instanceof ValidationFailedException ? iterator_to_array($exc->getViolations()) : null,
                        'exc' => $exc,
                        'command' => $command,
                    ]);
                }

                $reporter->acknowledgeNewErrors(); // we go to the next feature -> we reset the reporter's _hasNewErrors attribute
            }

            $numberOfRegulationsInsideEudonet = $this->eudonetParisExtractor->getNumberOfRegulations();
            $numberOfMeasuresInsideEudonet = $this->eudonetParisExtractor->getNumberOfMeasures();
        } catch (\Exception $exc) {
            $reporter->addError($reporter::ERROR_IMPORT_COMMAND_FAILED, [
                'message' => $exc->getMessage(),
            ]);
            throw new EudonetParisException($exc->getMessage());
        } finally {
            $reporter->addNotice('report', [
                // 'numProcessed' => $numProcessed,
                // 'numCreated' => $numCreated,
                // 'percentCreated' => round($numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0, 1),
                // 'numSkipped' => $numSkipped,
                // 'numSkippedNoLocationsGathered' => $numSkippedNoLocationsGathered,
                // 'percentSkipped' => round($numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0, 1),
                // 'numErrors' => $numErrors,
                // 'percentErrors' => round($numProcessed > 0 ? 100 * $numErrors / $numProcessed : 0, 1),
                // 'elapsedSeconds' => round($elapsedSeconds, 2),
                'numberOfRegulationsInsideEudonet' => $numberOfRegulationsInsideEudonet,
                'numberOfMeasuresInsideEudonet' => $numberOfMeasuresInsideEudonet,
            ]);

            $endTime = $this->dateUtils->getNow();
            $reporter->end(endTime: $endTime);
        }
    }
}

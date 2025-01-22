<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Integration\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\Integration\EudonetParis\Exception\EudonetParisException;
use Psr\Log\LoggerInterface;

final class EudonetParisExecutor
{
    public function __construct(
        private LoggerInterface $logger,
        private EudonetParisExtractor $eudonetParisExtractor,
        private EudonetParisTransformer $eudonetParisTransformer,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private string $eudonetParisOrgId,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function execute(\DateTimeInterface $laterThanUTC): void
    {
        if (!$this->eudonetParisOrgId) {
            throw new EudonetParisException('No target organization ID set. Please set APP_EUDONET_PARIS_ORG_ID in .env.local');
        }

        $numProcessed = 0;
        $numCreated = 0;
        $numSkipped = 0;
        $numSkippedNoLocationsGathered = 0;
        $numErrors = 0;
        $startTime = $this->dateUtils->getMicroTime();
        $numberOfRegulationsInsideEudonet = null;
        $numberOfMeasuresInsideEudonet = null;

        $this->logger->info('started');

        try {
            try {
                $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->eudonetParisOrgId));
            } catch (OrganizationNotFoundException $exc) {
                throw new EudonetParisException("Organization not found: $this->eudonetParisOrgId");
            }

            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSource(RegulationOrderRecordSourceEnum::EUDONET_PARIS->value);

            foreach ($this->eudonetParisExtractor->iterExtract($laterThanUTC, ignoreIDs: $existingIdentifiers) as $record) {
                $result = $this->eudonetParisTransformer->transform($record, $organization);

                ++$numProcessed;

                if (empty($result->command)) {
                    $this->logger->info('skipped', $result->errors);

                    ++$numSkipped;

                    foreach ($result->errors as $error) {
                        if ($error['reason'] == 'no_locations_gathered') {
                            ++$numSkippedNoLocationsGathered;
                            break;
                        }
                    }
                } else {
                    try {
                        $this->commandBus->handle($result->command);
                        $this->logger->info('created', ['command' => $result->command]);
                        ++$numCreated;
                    } catch (ImportEudonetParisRegulationFailedException $exc) {
                        $this->logger->error('failed', ['command' => $result->command, 'exc' => $exc]);
                        ++$numErrors;
                    }
                }
            }

            $numberOfRegulationsInsideEudonet = $this->eudonetParisExtractor->getNumberOfRegulations();
            $numberOfMeasuresInsideEudonet = $this->eudonetParisExtractor->getNumberOfMeasures();
        } catch (\Exception $exc) {
            $this->logger->error($exc);

            throw new EudonetParisException($exc->getMessage());
        } finally {
            $endTime = $this->dateUtils->getMicroTime();
            $elapsedSeconds = $endTime - $startTime;

            $this->logger->info('done', [
                'numProcessed' => $numProcessed,
                'numCreated' => $numCreated,
                'percentCreated' => round($numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0, 1),
                'numSkipped' => $numSkipped,
                'numSkippedNoLocationsGathered' => $numSkippedNoLocationsGathered,
                'percentSkipped' => round($numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0, 1),
                'numErrors' => $numErrors,
                'percentErrors' => round($numProcessed > 0 ? 100 * $numErrors / $numProcessed : 0, 1),
                'elapsedSeconds' => round($elapsedSeconds, 2),
                'numberOfRegulationsInsideEudonet' => $numberOfRegulationsInsideEudonet,
                'numberOfMeasuresInsideEudonet' => $numberOfMeasuresInsideEudonet,
            ]);
        }
    }
}

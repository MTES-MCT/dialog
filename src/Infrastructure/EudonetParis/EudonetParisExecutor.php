<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\EudonetParis\Exception\ImportEudonetParisRegulationFailedException;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\EudonetParis\Exception\EudonetParisException;
use Psr\Log\LoggerInterface;

final class EudonetParisExecutor
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $eudonetParisImportLogger,
        private EudonetParisExtractor $eudonetParisExtractor,
        private EudonetParisTransformer $eudonetParisTransformer,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private string $eudonetParisOrgId,
        private DateUtilsInterface $dateUtils,
    ) {
        $this->logger = $eudonetParisImportLogger;
    }

    public function execute(\DateTimeInterface $laterThanUTC): void
    {
        if (!$this->eudonetParisOrgId) {
            throw new EudonetParisException('No target organization ID set. Please set APP_EUDONET_PARIS_ORG_ID in .env.local');
        }

        $numProcessed = 0;
        $numCreated = 0;
        $numSkipped = 0;
        $numErrors = 0;
        $startTime = $this->dateUtils->getMicroTime();

        $this->logger->debug('started');

        try {
            try {
                $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->eudonetParisOrgId));
            } catch (OrganizationNotFoundException $exc) {
                throw new EudonetParisException("Organization not found: $this->eudonetParisOrgId");
            }

            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSourceInOrganization(RegulationOrderRecordSourceEnum::EUDONET_PARIS->value, $organization);

            foreach ($this->eudonetParisExtractor->iterExtract($laterThanUTC, ignoreIDs: $existingIdentifiers) as $record) {
                $result = $this->eudonetParisTransformer->transform($record, $organization);

                ++$numProcessed;

                if (empty($result->command)) {
                    $this->logger->debug('skipped', $result->skipMessages);
                    ++$numSkipped;
                } else {
                    try {
                        $this->commandBus->handle($result->command);
                        $this->logger->debug('created', ['command' => $result->command]);
                        ++$numCreated;
                    } catch (ImportEudonetParisRegulationFailedException $exc) {
                        $this->logger->error('failed', ['command' => $result->command, 'exc' => $exc]);
                        ++$numErrors;
                    }
                }
            }
        } catch (\Exception $exc) {
            $this->logger->error($exc);

            throw new EudonetParisException($exc->getMessage());
        } finally {
            $endTime = $this->dateUtils->getMicroTime();
            $elapsedSeconds = $endTime - $startTime;

            $this->logger->debug('done', [
                'numProcessed' => $numProcessed,
                'numCreated' => $numCreated,
                'percentCreated' => round($numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0, 1),
                'numSkipped' => $numSkipped,
                'percentSkipped' => round($numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0, 1),
                'numErrors' => $numErrors,
                'percentErrors' => round($numProcessed > 0 ? 100 * $numErrors / $numProcessed : 0, 1),
                'elapsedSeconds' => round($elapsedSeconds, 2),
            ]);
        }
    }
}

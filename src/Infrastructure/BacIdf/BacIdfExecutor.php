<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Infrastructure\BacIdf\Exception\BacIdfException;
use Psr\Log\LoggerInterface;

final class BacIdfExecutor
{
    public function __construct(
        private LoggerInterface $logger,
        private BacIdfExtractor $extractor,
        private BacIdfTransformer $transformer,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private string $bacIdfOrgId,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function execute(): void
    {
        if (!$this->bacIdfOrgId) {
            throw new BacIdfException('No target organization ID set. Please set APP_BAC_IDF_ORG_ID in .env.local');
        }

        $numProcessed = 0;
        $numCreated = 0;
        $numSkipped = 0;
        $numSkippedNotCirculation = 0;
        $numErrors = 0;
        $startTime = $this->dateUtils->getMicroTime();

        $this->logger->info('started');

        try {
            try {
                $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->bacIdfOrgId));
            } catch (OrganizationNotFoundException $exc) {
                throw new BacIdfException("Organization not found: $this->bacIdfOrgId");
            }

            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSourceInOrganization(RegulationOrderRecordSourceEnum::BAC_IDF->value, $organization);

            foreach ($this->extractor->iterExtract(ignoreIDs: $existingIdentifiers) as $record) {
                $this->logger->debug('before-transform', ['record' => $record]);
                $result = $this->transformer->transform($record, $organization);

                ++$numProcessed;

                if (empty($result->command)) {
                    $this->logger->info('skipped', $result->errors);
                    ++$numSkipped;
                    foreach ($result->errors as $error) {
                        if ($error['reason'] == 'value_not_expected' && $error['expected'] === 'CIRCULATION') {
                            ++$numSkippedNotCirculation;
                            break;
                        }
                    }
                } else {
                    try {
                        $this->commandBus->handle($result->command);
                        $this->logger->info('created', ['command' => $result->command]);
                        ++$numCreated;
                    } catch (ImportBacIdfRegulationFailedException $exc) {
                        $this->logger->error('failed', ['command' => $result->command, 'exc' => $exc]);
                        ++$numErrors;
                    }
                }
            }
        } catch (\Exception $exc) {
            $this->logger->error('exception', ['exc' => $exc]);

            throw new BacIdfException($exc->getMessage());
        } finally {
            $endTime = $this->dateUtils->getMicroTime();
            $elapsedSeconds = $endTime - $startTime;

            $this->logger->info('done', [
                'numProcessed' => $numProcessed,
                'numCreated' => $numCreated,
                'percentCreated' => round($numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0, 1),
                'numSkipped' => $numSkipped,
                'numSkippedNotCirculation' => $numSkippedNotCirculation,
                'percentSkipped' => round($numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0, 1),
                'numErrors' => $numErrors,
                'percentErrors' => round($numProcessed > 0 ? 100 * $numErrors / $numProcessed : 0, 1),
                'elapsedSeconds' => round($elapsedSeconds, 2),
            ]);
        }
    }
}

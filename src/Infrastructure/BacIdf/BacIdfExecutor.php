<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Infrastructure\BacIdf\Exception\BacIdfException;
use Psr\Log\LoggerInterface;

final class BacIdfExecutor
{
    public function __construct(
        private LoggerInterface $logger,
        private BacIdfExtractor $extractor,
        private BacIdfTransformer $transformer,
        private CommandBusInterface $commandBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function execute(): void
    {
        $numProcessed = 0;
        $numCreated = 0;
        $numSkipped = 0;
        $numSkippedNotCirculation = 0;
        $numErrors = 0;
        $startTime = $this->dateUtils->getMicroTime();

        $this->logger->info('started');

        try {
            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSource(RegulationOrderRecordSourceEnum::BAC_IDF->value);

            foreach ($this->extractor->iterExtract(ignoreIDs: $existingIdentifiers) as $record) {
                $this->logger->debug('before-transform', ['record' => $record]);
                $result = $this->transformer->transform($record);

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
                    if ($result->organizationCommand !== null) {
                        $organization = $this->commandBus->handle($result->organizationCommand);
                        $this->logger->info('organization:created', ['command' => $result->organizationCommand]);
                    } else {
                        $organization = $result->organization;
                    }

                    $result->command->generalInfoCommand->organization = $organization;

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

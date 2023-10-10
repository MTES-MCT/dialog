<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Exception\ImportBacIdfRegulationFailedException;
use App\Application\CommandBusInterface;
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
        $numErrors = 0;

        $this->logger->debug('started');

        try {
            try {
                $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->bacIdfOrgId));
            } catch (OrganizationNotFoundException $exc) {
                throw new BacIdfException("Organization not found: $this->bacIdfOrgId");
            }

            $existingIdentifiers = $this->regulationOrderRecordRepository
                ->findIdentifiersForSourceInOrganization(RegulationOrderRecordSourceEnum::BAC_IDF->value, $organization);

            foreach ($this->extractor->iterExtract(ignoreIDs: $existingIdentifiers) as $record) {
                $result = $this->transformer->transform($record, $organization);

                ++$numProcessed;

                if (empty($result->command)) {
                    $this->logger->debug('skipped', $result->messages);
                    ++$numSkipped;
                } else {
                    try {
                        $this->commandBus->handle($result->command);
                        $this->logger->debug('created', ['command' => $result->command]);
                        ++$numCreated;
                    } catch (ImportBacIdfRegulationFailedException $exc) {
                        $this->logger->error('failed', ['command' => $result->command, 'exc' => $exc]);
                        ++$numErrors;
                    }
                }
            }
        } catch (\Exception $exc) {
            $this->logger->error($exc);

            throw new BacIdfException($exc->getMessage());
        } finally {
            $this->logger->debug('done', [
                'numProcessed' => $numProcessed,
                'numCreated' => $numCreated,
                'percentCreated' => round($numProcessed > 0 ? 100 * $numCreated / $numProcessed : 0, 1),
                'numSkipped' => $numSkipped,
                'percentSkipped' => round($numProcessed > 0 ? 100 * $numSkipped / $numProcessed : 0, 1),
                'numErrors' => $numErrors,
                'percentErrors' => round($numProcessed > 0 ? 100 * $numErrors / $numProcessed : 0, 1),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class LitteralisExecutor
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LitteralisExtractor $extractor,
        private LitteralisTransformer $transformer,
        private LitteralisReporterFactory $reporterFactory,
        private LitteralisReportFormatter $reportFormatter,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function execute(string $orgId): string
    {
        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($orgId));
        } catch (OrganizationNotFoundException $exc) {
            throw new \RuntimeException(\sprintf('Organization not found: %s', $orgId));
        }

        $reporter = $this->reporterFactory->createReporter();
        $startTime = $this->dateUtils->getNow();
        $reporter->start(startTime: $startTime);

        $featuresByRegulation = $this->extractor->extractFeaturesByRegulation($reporter);

        foreach ($featuresByRegulation as $regulationFeatures) {
            $command = $this->transformer->transform($reporter, $regulationFeatures, $organization);

            if ($command === null) {
                // If errors have occurred, they have already been logged to the reporter by the transformer,
                // so we should just continue to the next set of features.
                $reporter->acknowledgeNewErrors();
                continue;
            }

            try {
                $this->commandBus->handle($command);
            } catch (\Exception $exc) {
                $reporter->addError($reporter::ERROR_IMPORT_COMMAND_FAILED, [
                    'message' => $exc->getMessage(),
                    'violations' => $exc instanceof ValidationFailedException ? iterator_to_array($exc->getViolations()) : null,
                    'command' => $command,
                ]);
            }

            $reporter->acknowledgeNewErrors();
        }

        $reporter->end(endTime: $this->dateUtils->getNow());

        return $this->reportFormatter->format($reporter->getRecords());
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\Litteralis\Command\CleanUpLitteralisRegulationsBeforeImportCommand;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\IntegrationReport\ReportFormatter;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class LitteralisExecutor
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private LitteralisExtractor $extractor,
        private LitteralisTransformer $transformer,
        private ReportFormatter $reportFormatter,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function configure(string $credentials): void
    {
        $this->extractor->configure($credentials);
    }

    public function execute(string $name, string $orgId, \DateTimeInterface $laterThan, Reporter $reporter): string
    {
        try {
            /** @var Organization */
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($orgId));
        } catch (OrganizationNotFoundException $exc) {
            throw new \RuntimeException(\sprintf('Organization not found: %s', $orgId));
        }

        $startTime = $this->dateUtils->getNow();
        $reporter->start($name, $startTime, $organization);

        $this->commandBus->handle(new CleanUpLitteralisRegulationsBeforeImportCommand($organization->getUuid(), $laterThan));

        $featuresByRegulation = $this->extractor->extractFeaturesByRegulation($laterThan, $reporter);
        $numImportedRegulations = 0;
        $numImportedFeatures = 0;

        foreach ($featuresByRegulation as $identifier => $regulationFeatures) {
            $command = $this->transformer->transform($reporter, $identifier, $regulationFeatures, $organization);

            if ($command === null) {
                // If errors have occurred, they have already been logged to the reporter by the transformer,
                // so we should just continue to the next set of features.
                $reporter->acknowledgeNewErrors();
                continue;
            }

            try {
                $this->commandBus->handle($command);
                ++$numImportedRegulations;
                $numImportedFeatures += \count($regulationFeatures);
            } catch (\Exception $exc) {
                $reporter->addError(LitteralisRecordEnum::ERROR_IMPORT_COMMAND_FAILED->value, [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $regulationFeatures[0]['properties']['arretesrcid'],
                    CommonRecordEnum::ATTR_URL->value => $regulationFeatures[0]['properties']['shorturl'],
                    'message' => $exc->getMessage(),
                    'violations' => $exc instanceof ValidationFailedException ? iterator_to_array($exc->getViolations()) : null,
                    'command' => $command,
                ]);
            }

            $reporter->acknowledgeNewErrors();
        }

        $reporter->addCount(LitteralisRecordEnum::COUNT_IMPORTED_FEATURES->value, $numImportedFeatures, ['regulationsCount' => $numImportedRegulations]);

        $reporter->end(endTime: $this->dateUtils->getNow());
        $report = $this->reportFormatter->format($reporter->getRecords());
        $reporter->onReport($report);

        return $report;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\JOP;

use App\Application\CommandBusInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Application\Regulation\Query\GetRegulationOrderRecordByUuidQuery;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class JOPExecutor
{
    public function __construct(
        private LoggerInterface $logger,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private JOPExtractor $jopExtractor,
        private JOPTransformer $jopTransformer,
        private string $jopOrgId,
    ) {
    }

    public function execute(): void
    {
        if (!$this->jopOrgId) {
            throw new \RuntimeException('No target organization ID set. Please set APP_JOP_ORG_ID');
        }

        try {
            $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($this->jopOrgId));
        } catch (OrganizationNotFoundException $exc) {
            throw new \RuntimeException(\sprintf('Organization not found: %s', $this->jopOrgId));
        }

        $this->logger->info('started');

        $geoJSON = $this->jopExtractor->extractGeoJSON();
        $command = $this->jopTransformer->transform($geoJSON, $organization);

        // Delete existing JOP regulation order if it exists
        $existingUuid = $this->regulationOrderRecordRepository->findOneUuidByIdentifierInOrganization(JOPTransformer::JOP_REGULATION_ORDER_IDENTIFIER, $organization);

        if ($existingUuid) {
            $regulationOrderRecord = $this->queryBus->handle(new GetRegulationOrderRecordByUuidQuery($existingUuid));
            $this->commandBus->handle(new DeleteRegulationCommand([$this->jopOrgId], $regulationOrderRecord));
        }

        $this->logger->info('import:start');

        try {
            $this->commandBus->handle($command);
        } catch (\RuntimeException $exc) {
            $context = ['message' => $exc->getMessage(), 'violations' => $exc instanceof ValidationFailedException ? $exc->getViolations() : null];
            $this->logger->error('import:error', $context);
            throw $exc;
        }

        $this->logger->info('done');
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\DateUtilsInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\Adapter\OrganizationAdministrativeBoundariesGeometry;
use Psr\Log\LoggerInterface;

final class SyncOrganizationAdministrativeBoundariesCommandHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private OrganizationAdministrativeBoundariesGeometry $administrativeBoundariesGeometry,
        private DateUtilsInterface $dateUtils,
        private LoggerInterface $organizationGeometryImportLogger,
    ) {
    }

    public function __invoke(SyncOrganizationAdministrativeBoundariesCommand $command): void
    {
        $organization = $this->organizationRepository->findOneByUuid($command->organizationUuid);
        if (!$organization) {
            $this->organizationGeometryImportLogger->warning('Organization not found', [
                'organizationUuid' => $command->organizationUuid,
            ]);

            throw new OrganizationNotFoundException();
        }

        try {
            $geometry = $this->administrativeBoundariesGeometry->findByCodes($organization->getCode(), $organization->getCodeType());
            $this->organizationGeometryImportLogger->info('Organization geometry synced', [
                'siret' => $organization->getSiret(),
                'name' => $organization->getName(),
            ]);
        } catch (\Exception $e) {
            $this->organizationGeometryImportLogger->error('Impossible to get organization geometry', [
                'siret' => $organization->getSiret(),
                'name' => $organization->getName(),
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $organization
            ->setGeometry($geometry)
            ->setUpdatedAt($this->dateUtils->getNow());
    }
}

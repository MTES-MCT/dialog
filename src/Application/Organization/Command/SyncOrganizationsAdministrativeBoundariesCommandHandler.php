<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class SyncOrganizationsAdministrativeBoundariesCommandHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private ApiOrganizationFetcherInterface $apiOrganizationFetcher,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SyncOrganizationsAdministrativeBoundariesCommand $command): SyncOrganizationsAdministrativeBoundariesCommandResult
    {
        $organizations = $this->organizationRepository->findAllEntities();
        $totalOrganizations = \count($organizations);
        $exceptions = [];
        $updatedOrganizations = 0;

        foreach ($organizations as $organization) {
            try {
                $organizationFetchedView = $this->apiOrganizationFetcher->findBySiret($organization->getSiret());
                $organization
                    ->setName($organizationFetchedView->name)
                    ->setGeometry($organizationFetchedView->geometry)
                    ->setCode($organizationFetchedView->code)
                    ->setCodeType($organizationFetchedView->codeType)
                    ->setUpdatedAt($this->dateUtils->getNow());
                ++$updatedOrganizations;
            } catch (\Exception $e) {
                $exceptions[] = $e;

                continue;
            }
        }

        return new SyncOrganizationsAdministrativeBoundariesCommandResult($totalOrganizations, $updatedOrganizations, $exceptions);
    }
}

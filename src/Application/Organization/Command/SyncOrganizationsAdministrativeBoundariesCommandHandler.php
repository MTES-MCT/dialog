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
        $updatedOrganizations = 0;

        foreach ($organizations as $organization) {
            $organizationFetchedView = $this->apiOrganizationFetcher->findBySiret($organization->getSiret());
            if (!$organizationFetchedView->geometry) {
                continue;
            }

            $organization
                ->setName($organizationFetchedView->name)
                ->setGeometry($organizationFetchedView->geometry)
                ->setCode($organizationFetchedView->code)
                ->setCodeType($organizationFetchedView->codeType)
                ->setUpdatedAt($this->dateUtils->getNow());
            ++$updatedOrganizations;
        }

        return new SyncOrganizationsAdministrativeBoundariesCommandResult($totalOrganizations, $updatedOrganizations);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class GetOrCreateOrganizationBySiretCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationRepositoryInterface $organizationRepository,
        private DateUtilsInterface $dateUtils,
        private ApiOrganizationFetcherInterface $organizationFetcher,
    ) {
    }

    public function __invoke(GetOrCreateOrganizationBySiretCommand $command): GetOrCreateOrganizationView
    {
        $siret = $command->siret;
        $organization = $this->organizationRepository->findOneBySiret($siret);
        $now = $this->dateUtils->getNow();
        $isCreated = false;

        if (!$organization) {
            try {
                ['name' => $name] = $this->organizationFetcher->findBySiret($siret);
            } catch (OrganizationNotFoundException $e) {
                throw $e;
            }

            $isCreated = true;
            $organization = (new Organization($this->idFactory->make()))
                ->setCreatedAt($now)
                ->setSiret($siret)
                ->setName($name);

            $this->organizationRepository->add($organization);
        }

        return new GetOrCreateOrganizationView($organization, $isCreated);
    }
}

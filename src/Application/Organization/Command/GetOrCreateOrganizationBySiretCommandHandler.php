<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class GetOrCreateOrganizationBySiretCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationRepositoryInterface $organizationRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private DateUtilsInterface $dateUtils,
        private ApiOrganizationFetcherInterface $organizationFetcher,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(GetOrCreateOrganizationBySiretCommand $command): GetOrCreateOrganizationView
    {
        $siret = $command->siret;
        $organization = $this->organizationRepository->findOneBySiret($siret);
        $now = $this->dateUtils->getNow();

        if (!$organization) {
            try {
                /** @var OrganizationFetchedView */
                $organizationFetchedView = $this->organizationFetcher->findBySiret($siret);
            } catch (OrganizationNotFoundException $e) {
                throw $e;
            }

            $organization = (new Organization($this->idFactory->make()))
                ->setCreatedAt($now)
                ->setSiret($siret)
                ->setName($organizationFetchedView->name)
                ->setCode($organizationFetchedView->code)
                ->setCodeType($organizationFetchedView->codeType);

            $this->organizationRepository->add($organization);
            $this->commandBus->dispatchAsync(new SyncOrganizationAdministrativeBoundariesCommand($organization->getUuid()));
        }

        return new GetOrCreateOrganizationView(
            organization: $organization,
            hasOrganizationUsers: \count($this->organizationUserRepository->findByOrganizationUuid($organization->getUuid())) > 0,
        );
    }
}

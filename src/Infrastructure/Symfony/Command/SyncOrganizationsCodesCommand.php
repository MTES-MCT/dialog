<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\View\OrganizationFetchedView;
use App\Domain\Organization\Establishment\Establishment;
use App\Domain\Organization\Establishment\Repository\EstablishmentRepositoryInterface;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:organizations:codes:sync',
    description: 'Sync organizations codes',
    hidden: false,
)]
class SyncOrganizationsCodesCommand extends Command
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private ApiOrganizationFetcherInterface $organizationFetcher,
        private EstablishmentRepositoryInterface $establishmentRepository,
        private IdFactoryInterface $idFactory,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizations = $this->organizationRepository->findAllEntities();

        foreach ($organizations as $organization) {
            try {
                /** @var OrganizationFetchedView */
                $organizationFetchedView = $this->organizationFetcher->findBySiret($organization->getSiret());
                usleep(150000); // 150 ms de délai pour ne pas dépasser 7 req/s
                $organization
                    ->setCode($organizationFetchedView->code)
                    ->setCodeType($organizationFetchedView->codeType)
                    ->setDepartmentName($organizationFetchedView->departmentName)
                    ->setDepartmentCode($organizationFetchedView->departmentCode);

                if ($organizationFetchedView->establishmentAddress && $organizationFetchedView->establishmentZipCode && $organizationFetchedView->establishmentCity) {
                    if ($establishment = $organization->getEstablishment()) {
                        $establishment->update(
                            address: $organizationFetchedView->establishmentAddress,
                            zipCode: $organizationFetchedView->establishmentZipCode,
                            city: $organizationFetchedView->establishmentCity,
                            addressComplement: $organizationFetchedView->establishmentAddressComplement,
                        );
                    } else {
                        $establishment = new Establishment(
                            uuid: $this->idFactory->make(),
                            address: $organizationFetchedView->establishmentAddress,
                            zipCode: $organizationFetchedView->establishmentZipCode,
                            city: $organizationFetchedView->establishmentCity,
                            organization: $organization,
                            addressComplement: $organizationFetchedView->establishmentAddressComplement,
                        );
                        $this->establishmentRepository->add($establishment);
                        $organization->setEstablishment($establishment);
                    }
                }

                $this->entityManager->flush();
                $output->writeln(\sprintf('<info>%s - %s</info>', $organization->getSiret(), $organization->getName()));
            } catch (OrganizationNotFoundException) {
                $output->writeln(\sprintf('<error>%s - %s</error>', $organization->getSiret(), $organization->getName()));

                continue;
            }
        }

        return Command::SUCCESS;
    }
}

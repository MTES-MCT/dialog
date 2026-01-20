<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Infrastructure\CRM\GristClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:grist:sync-organizations',
    description: 'Synchronise les organisations avec Grist pour mettre à jour le CRM',
    hidden: false,
)]
final class SyncOrganizationsToGristCommand extends Command
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly GristClient $gristClient,
        #[Autowire(env: 'GRIST_ORGANIZATION_TABLE_ID')]
        private string $organizationTableId,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation des organisations vers Grist');

        $organizations = $this->organizationRepository->findAllEntities();
        $io->info(\sprintf('Récupération de %d organisation(s)', \count($organizations)));

        if (0 === \count($organizations)) {
            $io->warning('Aucune organisation à synchroniser');

            return Command::SUCCESS;
        }

        try {
            $io->info('Envoi des données vers Grist...');

            $records = [];

            foreach ($organizations as $organization) {
                $type = match ($organization->getCodeType()) {
                    OrganizationCodeTypeEnum::INSEE->value => 'Commune',
                    OrganizationCodeTypeEnum::EPCI->value => 'EPCI',
                    OrganizationCodeTypeEnum::DEPARTMENT->value => 'Département',
                    OrganizationCodeTypeEnum::REGION->value => 'Région',
                    default => null,
                };

                $records[] = [
                    'require' => ['siret' => $organization->getSiret()],
                    'fields' => [
                        'nom' => $organization->getName(),
                        'type' => $type,
                        'departement' => $organization->getDepartmentCode(),
                        'siret' => $organization->getSiret(),
                        'code_insee' => $organization->getCode(),
                    ],
                ];
            }

            $this->gristClient->syncData($records, $this->organizationTableId);
            $io->success(\sprintf('Synchronisation réussie : %d organisation(s) synchronisée(s) avec Grist', \count($organizations)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Erreur lors de la synchronisation : %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}

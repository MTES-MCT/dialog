<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Infrastructure\CRM\GristClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:grist:sync-users',
    description: 'Synchronise les utilisateurs avec Grist pour mettre à jour le CRM',
    hidden: false,
)]
final class SyncUsersToGristCommand extends Command
{
    public function __construct(
        private readonly OrganizationUserRepositoryInterface $organizationUserRepository,
        private readonly RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
        private readonly GristClient $gristClient,
        #[Autowire(env: 'GRIST_CONTACT_TABLE_ID')]
        private string $contactTableId,
        #[Autowire(env: 'GRIST_ORGANIZATION_TABLE_ID')]
        private string $organizationTableId,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Synchronisation des utilisateurs vers Grist');

        $rows = $this->organizationUserRepository->findAllUsersWithOrganizations();
        $io->info(\sprintf('Récupération de %d ligne(s)', \count($rows)));

        if (0 === \count($rows)) {
            $io->warning('Aucun utilisateur à synchroniser');

            return Command::SUCCESS;
        }

        try {
            $io->info('Récupération des statistiques d\'arrêtés créés...');
            $userUuids = array_unique(array_column($rows, 'userUuid'));
            $regulationOrderCounts = $this->regulationOrderHistoryRepository->countCreatedRegulationOrdersByUserUuids($userUuids);

            $io->info('Récupération des organisations depuis Grist...');

            // Récupération des organisations depuis Grist
            $gristOrganizations = $this->gristClient->getRecords($this->organizationTableId);

            $organizationNameToId = [];
            foreach ($gristOrganizations as $org) {
                if (isset($org['fields']['nom']) && isset($org['id'])) {
                    $organizationNameToId[$org['fields']['nom']] = $org['id'];
                }
            }

            $io->info('Envoi des données vers Grist...');
            $records = [];
            foreach ($rows as $row) {
                $email = $row['email'];
                if (!isset($records[$email])) {
                    $records[$email] = [
                        'require' => ['email' => $email],
                        'fields' => [
                            'full_name' => $row['fullName'],
                            'email' => $email,
                            'organisations' => [],
                            'registration_date' => $row['registrationDate']->format('Y-m-d H:i:s'),
                            'last_activity_date' => $row['lastActiveAt']?->format('Y-m-d H:i:s'),
                            'user_uuid' => $row['userUuid'],
                            'created_regulation_orders_count' => $regulationOrderCounts[$row['userUuid']] ?? 0,
                        ],
                    ];
                }

                // Utiliser l'ID Grist de l'organisation si disponible
                $orgName = $row['organizationName'];
                if (isset($organizationNameToId[$orgName])) {
                    $records[$email]['fields']['organisations'][] = $organizationNameToId[$orgName];
                } else {
                    $io->warning(\sprintf('Organisation "%s" non trouvée dans Grist, ignorée pour l\'utilisateur %s', $orgName, $email));
                }
            }

            // Convertir en tableau indexé et finaliser les records
            $records = array_map(
                function (array $record) {
                    unset($record['fields']['user_uuid']);

                    // Formater les organisations au format Grist pour les références multiples : ["L", id1, id2, ...]
                    // Le code "L" représente une List dans Grist
                    if (!empty($record['fields']['organisations'])) {
                        $orgIds = $record['fields']['organisations'];
                        $record['fields']['organisations'] = array_merge(['L'], $orgIds);
                    } else {
                        // Si aucune orgagnisation, envoyer null au lieu d'un tableau vide
                        $record['fields']['organisations'] = null;
                    }

                    return $record;
                },
                array_values($records),
            );

            $this->gristClient->syncData($records, $this->contactTableId);
            $io->success(\sprintf('Synchronisation réussie : %d utilisateur(s) synchronisé(s) avec Grist', \count($records)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Erreur lors de la synchronisation : %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}

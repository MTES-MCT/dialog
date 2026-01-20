<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
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
        private readonly UserRepositoryInterface $userRepository,
        private readonly OrganizationUserRepositoryInterface $organizationUserRepository,
        private readonly GristClient $gristClient,
        #[Autowire(env: 'GRIST_CONTACT_TABLE_ID')]
        private string $contactTableId,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Synchronisation des utilisateurs vers Grist');

        $users = $this->userRepository->findAll();
        $io->info(\sprintf('Récupération de %d utilisateur(s)', \count($users)));

        if (0 === \count($users)) {
            $io->warning('Aucun utilisateur à synchroniser');

            return Command::SUCCESS;
        }

        try {
            $io->info('Envoi des données vers Grist...');
            $records = [];

            foreach ($users as $user) {
                $userOrganizations = $this->organizationUserRepository->findByUserUuid($user->getUuid());
                $records[] = [
                    'require' => ['email' => $user->getEmail()],
                    'fields' => [
                        'full_name' => $user->getFullName(),
                        'email' => $user->getEmail(),
                        'organisations' => implode(', ', array_map(fn (UserOrganizationView $userOrganization) => $userOrganization->name, $userOrganizations)),
                        'registration_date' => $user->getRegistrationDate()->format('Y-m-d H:i:s'),
                        'last_activity_date' => $user->getLastActiveAt()?->format('Y-m-d H:i:s'),
                    ],
                ];
            }

            $this->gristClient->syncData($records, $this->contactTableId);
            $io->success(\sprintf('Synchronisation réussie : %d utilisateur(s) synchronisé(s) avec Grist', \count($users)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Erreur lors de la synchronisation : %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}

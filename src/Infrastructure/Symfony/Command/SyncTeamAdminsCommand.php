<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:team:sync-admins',
    description: 'Synchronise les administrateurs internes de l\'équipe (config/team_admins.yaml) avec la base.',
    hidden: false,
)]
final class SyncTeamAdminsCommand extends Command
{
    private const CONFIG_RELATIVE_PATH = 'config/team_admins.yaml';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordUserRepositoryInterface $passwordUserRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private IdFactoryInterface $idFactory,
        private DateUtilsInterface $dateUtils,
        private StringUtilsInterface $stringUtils,
        private EntityManagerInterface $entityManager,
        private string $projectDir,
        private string $dialogOrgId,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'N\'applique rien, affiche uniquement le diff qui serait appliqué.',
        );
        $this->addOption(
            'config',
            null,
            InputOption::VALUE_REQUIRED,
            'Chemin vers un fichier YAML alternatif (défaut : config/team_admins.yaml).',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        $configOption = $input->getOption('config');
        $configPath = \is_string($configOption) && '' !== $configOption
            ? $configOption
            : $this->projectDir . '/' . self::CONFIG_RELATIVE_PATH;
        if (!is_file($configPath)) {
            $output->writeln(\sprintf('<error>Fichier de configuration introuvable : %s</error>', $configPath));

            return Command::FAILURE;
        }

        /** @var array{admins?: array<int, array<string, string>>} $config */
        $config = Yaml::parseFile($configPath);

        $admins = $config['admins'] ?? [];

        $organization = $this->organizationRepository->findOneByUuid($this->dialogOrgId);
        if (null === $organization) {
            $output->writeln(\sprintf('<error>Organisation équipe introuvable en base : %s</error>', $this->dialogOrgId));

            return Command::FAILURE;
        }

        $desiredByEmail = [];
        foreach ($admins as $index => $admin) {
            if (!isset($admin['email'], $admin['full_name'])) {
                $output->writeln(\sprintf('<error>Entrée #%d invalide : `email` et `full_name` sont requis.</error>', $index));

                return Command::FAILURE;
            }
            $email = $this->stringUtils->normalizeEmail($admin['email']);
            if (isset($desiredByEmail[$email])) {
                $output->writeln(\sprintf('<error>Email dupliqué dans la config : %s</error>', $email));

                return Command::FAILURE;
            }
            $desiredByEmail[$email] = [
                'email' => $email,
                'full_name' => $admin['full_name'],
                'password_hash' => $admin['password_hash'] ?? null,
            ];
        }

        $existingSuperAdmins = $this->userRepository->findAllByRole(UserRolesEnum::ROLE_SUPER_ADMIN->value);
        $existingByEmail = [];
        foreach ($existingSuperAdmins as $user) {
            $existingByEmail[$this->stringUtils->normalizeEmail($user->getEmail())] = $user;
        }

        $created = $updated = $deleted = $skipped = 0;

        foreach ($desiredByEmail as $email => $desired) {
            if (isset($existingByEmail[$email])) {
                $user = $existingByEmail[$email];
                $changed = $this->updateExistingUser($user, $desired, $organization, $output, $dryRun);
                if ($changed) {
                    ++$updated;
                }
                continue;
            }

            $existing = $this->userRepository->findOneByEmail($email);
            if ($existing instanceof User) {
                $output->writeln(\sprintf('<info>[promote]</info> %s : ajout de ROLE_SUPER_ADMIN', $email));
                if (!$dryRun) {
                    $roles = $existing->getRoles();
                    if (!\in_array(UserRolesEnum::ROLE_SUPER_ADMIN->value, $roles, true)) {
                        $roles[] = UserRolesEnum::ROLE_SUPER_ADMIN->value;
                        $existing->setRoles($roles);
                    }
                    $existing->setFullName($desired['full_name']);
                    $existing->setIsVerified();
                    $this->ensureOrganizationLink($existing, $organization, $dryRun);
                }
                ++$updated;
                continue;
            }

            if (null === $desired['password_hash']) {
                $output->writeln(\sprintf(
                    '<comment>[skip]</comment> %s : utilisateur inexistant et aucun `password_hash` fourni (ignoré).',
                    $email,
                ));
                ++$skipped;
                continue;
            }

            $output->writeln(\sprintf('<info>[create]</info> %s (%s)', $email, $desired['full_name']));
            if (!$dryRun) {
                $user = (new User($this->idFactory->make()))
                    ->setFullName($desired['full_name'])
                    ->setEmail($email)
                    ->setRoles([UserRolesEnum::ROLE_SUPER_ADMIN->value])
                    ->setRegistrationDate($this->dateUtils->getNow())
                    ->setIsVerified();

                $passwordUser = new PasswordUser(
                    uuid: $this->idFactory->make(),
                    password: $desired['password_hash'],
                    user: $user,
                );
                $user->setPasswordUser($passwordUser);

                $this->userRepository->add($user);
                $this->passwordUserRepository->add($passwordUser);

                $organizationUser = (new OrganizationUser($this->idFactory->make()))
                    ->setUser($user)
                    ->setOrganization($organization)
                    ->setIsOwner(true);
                $this->organizationUserRepository->add($organizationUser);
            }
            ++$created;
        }

        foreach ($existingByEmail as $email => $user) {
            if (isset($desiredByEmail[$email])) {
                continue;
            }
            $output->writeln(\sprintf('<comment>[delete]</comment> %s (%s)', $email, $user->getFullName()));
            if (!$dryRun) {
                $this->userRepository->remove($user);
            }
            ++$deleted;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $output->writeln(\sprintf(
            '<info>Terminé%s — créés: %d, mis à jour: %d, supprimés: %d, ignorés: %d.</info>',
            $dryRun ? ' (dry-run)' : '',
            $created,
            $updated,
            $deleted,
            $skipped,
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array{email: string, full_name: string, password_hash: ?string} $desired
     */
    private function updateExistingUser(
        User $user,
        array $desired,
        Organization $organization,
        OutputInterface $output,
        bool $dryRun,
    ): bool {
        $changes = [];

        if ($user->getFullName() !== $desired['full_name']) {
            $changes[] = \sprintf('full_name "%s" → "%s"', $user->getFullName(), $desired['full_name']);
            if (!$dryRun) {
                $user->setFullName($desired['full_name']);
            }
        }

        if (!$user->isVerified()) {
            $changes[] = 'is_verified → true';
            if (!$dryRun) {
                $user->setIsVerified();
            }
        }

        $roles = $user->getRoles();
        if (!\in_array(UserRolesEnum::ROLE_SUPER_ADMIN->value, $roles, true)) {
            $roles[] = UserRolesEnum::ROLE_SUPER_ADMIN->value;
            $changes[] = 'roles += ROLE_SUPER_ADMIN';
            if (!$dryRun) {
                $user->setRoles($roles);
            }
        }

        $linkCreated = $this->ensureOrganizationLink($user, $organization, $dryRun);
        if ($linkCreated) {
            $changes[] = 'link to team organization';
        }

        if ([] === $changes) {
            return false;
        }

        $output->writeln(\sprintf('<info>[update]</info> %s : %s', $user->getEmail(), implode(', ', $changes)));

        return true;
    }

    private function ensureOrganizationLink(
        User $user,
        Organization $organization,
        bool $dryRun,
    ): bool {
        $existing = $this->organizationUserRepository->findOrganizationUser(
            $organization->getUuid(),
            $user->getUuid(),
        );
        if (null !== $existing) {
            return false;
        }
        if (!$dryRun) {
            $organizationUser = (new OrganizationUser($this->idFactory->make()))
                ->setUser($user)
                ->setOrganization($organization)
                ->setIsOwner(true);
            $this->organizationUserRepository->add($organizationUser);
        }

        return true;
    }
}

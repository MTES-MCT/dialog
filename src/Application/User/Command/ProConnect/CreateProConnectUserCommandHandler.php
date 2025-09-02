<?php

declare(strict_types=1);

namespace App\Application\User\Command\ProConnect;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\ProConnectUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\IsUserAlreadyRegisteredInOrganization;
use App\Domain\User\User;

final class CreateProConnectUserCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private UserRepositoryInterface $userRepository,
        private ProConnectUserRepositoryInterface $proConnectUserRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private IsUserAlreadyRegisteredInOrganization $isUserAlreadyRegisteredInOrganization,
        private DateUtilsInterface $dateUtils,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CreateProConnectUserCommand $command): void
    {
        $user = $this->userRepository->findOneByEmail($command->email);

        // Si le compte utilisateur a déjà un accès ProConnect, on stoppe le process
        if ($user?->getProConnectUser()) {
            return;
        }

        try {
            // Si le compte utilisateur n'existe pas (ni ProConnectUser, ni PasswordUser)
            if (!$user) {
                $user = (new User($this->idFactory->make()))
                    ->setFullName(\sprintf('%s %s', $command->givenName, $command->usualName))
                    ->setEmail($command->email)
                    ->setRoles([UserRolesEnum::ROLE_USER->value])
                    ->setRegistrationDate($this->dateUtils->getNow())
                    ->setIsVerified();

                $this->userRepository->add($user);
            }

            // Si l'utilisateur n'est pas dans l'organisation récupéré via ProConnect, on le rajoute, en créant l'orga si besoin.

            /** @var GetOrCreateOrganizationView $organizationView */
            $organizationView = $this->commandBus->handle(new GetOrCreateOrganizationBySiretCommand($command->siret));
            $organization = $organizationView->organization;

            if (!$this->isUserAlreadyRegisteredInOrganization->isSatisfiedBy($user->getEmail(), $organization)) {
                $organizationRole = $organizationView->hasOrganizationUsers
                    ? OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value
                    : OrganizationRolesEnum::ROLE_ORGA_ADMIN->value;

                $organizationUser = (new OrganizationUser($this->idFactory->make()))
                    ->setUser($user)
                    ->setOrganization($organization)
                    ->setRoles($organizationRole);
                $this->organizationUserRepository->add($organizationUser);
            }

            // Dernière étape, création du compte ProConnect
            $proConnectUser = new ProConnectUser($this->idFactory->make(), $user);
            $user->setProConnectUser($proConnectUser);
            $this->proConnectUserRepository->add($proConnectUser);
        } catch (OrganizationNotFoundException $e) {
            throw $e;
        }
    }
}

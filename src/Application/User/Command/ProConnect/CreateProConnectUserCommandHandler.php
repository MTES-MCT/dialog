<?php

declare(strict_types=1);

namespace App\Application\User\Command\ProConnect;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
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

        if ($user?->getProConnectUser()) {
            return;
        }

        try {
            if (!$user) {
                $user = (new User($this->idFactory->make()))
                    ->setFullName(\sprintf('%s %s', $command->givenName, $command->usualName))
                    ->setEmail($command->email)
                    ->setRoles([UserRolesEnum::ROLE_USER->value])
                    ->setRegistrationDate($this->dateUtils->getNow())
                    ->setIsVerified();

                $this->userRepository->add($user);
            }

            /** @var GetOrCreateOrganizationView $organizationView */
            $organizationView = $this->commandBus->handle(new GetOrCreateOrganizationBySiretCommand($command->siret));
            $organization = $organizationView->organization;

            if (!$this->isUserAlreadyRegisteredInOrganization->isSatisfiedBy($user->getEmail(), $organization)) {
                $isOwner = !$organizationView->hasOrganizationUsers;

                $organizationUser = (new OrganizationUser($this->idFactory->make()))
                    ->setUser($user)
                    ->setOrganization($organization)
                    ->setIsOwner($isOwner);
                $this->organizationUserRepository->add($organizationUser);
            }

            $proConnectUser = new ProConnectUser($this->idFactory->make(), $user);
            $user->setProConnectUser($proConnectUser);
            $this->proConnectUserRepository->add($proConnectUser);
        } catch (OrganizationNotFoundException $e) {
            throw $e;
        }
    }
}

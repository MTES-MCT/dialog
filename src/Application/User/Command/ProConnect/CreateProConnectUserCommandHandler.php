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
use App\Domain\User\User;

final class CreateProConnectUserCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private UserRepositoryInterface $userRepository,
        private ProConnectUserRepositoryInterface $proConnectUserRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private DateUtilsInterface $dateUtils,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CreateProConnectUserCommand $command): User
    {
        ['given_name' => $givenName, 'usual_name' => $usualName, 'siret' => $siret] = $command->userInfo;

        $user = $this->userRepository->findOneByEmail($command->email);
        if ($user instanceof User) {
            return $user;
        }

        try {
            /** @var GetOrCreateOrganizationView $organizationView */
            $organizationView = $this->commandBus->handle(new GetOrCreateOrganizationBySiretCommand($siret));
            $organization = $organizationView->organization;
            $organizationRole = $organizationView->isCreated
                ? OrganizationRolesEnum::ROLE_ORGA_ADMIN->value
                : OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;

            $now = $this->dateUtils->getNow();
            $user = (new User($this->idFactory->make()))
                ->setFullName(\sprintf('%s %s', $givenName, $usualName))
                ->setEmail($command->email)
                ->setRoles([UserRolesEnum::ROLE_USER->value])
                ->setRegistrationDate($now)
                ->setVerified();

            $proConnectUser = new ProConnectUser($this->idFactory->make(), $user);

            $user->setProConnectUser($proConnectUser);

            $organizationUser = (new OrganizationUser($this->idFactory->make()))
                ->setUser($user)
                ->setOrganization($organization)
                ->setRoles($organizationRole);

            $this->userRepository->add($user);
            $this->proConnectUserRepository->add($proConnectUser);
            $this->organizationUserRepository->add($organizationUser);

            return $user;
        } catch (OrganizationNotFoundException $e) {
            throw $e;
        }
    }
}

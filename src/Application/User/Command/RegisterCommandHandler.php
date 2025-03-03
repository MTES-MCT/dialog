<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\Command\GetOrCreateOrganizationBySiretCommand;
use App\Application\Organization\View\GetOrCreateOrganizationView;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final class RegisterCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private UserRepositoryInterface $userRepository,
        private PasswordUserRepositoryInterface $passwordUserRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private DateUtilsInterface $dateUtils,
        private StringUtilsInterface $stringUtils,
        private PasswordHasherInterface $passwordHasher,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(RegisterCommand $command): User
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        $user = $this->userRepository->findOneByEmail($email);
        if ($user instanceof User) {
            throw new UserAlreadyRegisteredException();
        }

        /** @var GetOrCreateOrganizationView $organizationView */
        $organizationView = $this->commandBus->handle(new GetOrCreateOrganizationBySiretCommand($command->organizationSiret));
        $organization = $organizationView->organization;
        $organizationRole = $organizationView->isCreated
            ? OrganizationRolesEnum::ROLE_ORGA_ADMIN->value
            : OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value;

        $now = $this->dateUtils->getNow();
        $user = (new User($this->idFactory->make()))
            ->setFullName($command->fullName)
            ->setEmail($email)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($now);

        $passwordUser = new PasswordUser(
            uuid: $this->idFactory->make(),
            password: $this->passwordHasher->hash($command->password),
            user: $user,
        );

        $user->setPasswordUser($passwordUser);

        $organizationUser = (new OrganizationUser($this->idFactory->make()))
            ->setUser($user)
            ->setOrganization($organization)
            ->setRoles($organizationRole);

        $this->userRepository->add($user);
        $this->passwordUserRepository->add($passwordUser);
        $this->organizationUserRepository->add($organizationUser);

        return $user;
    }
}

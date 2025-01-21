<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
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
        private OrganizationRepositoryInterface $organizationRepository,
        private DateUtilsInterface $dateUtils,
        private StringUtilsInterface $stringUtils,
        private PasswordHasherInterface $passwordHasher,
        private ApiOrganizationFetcherInterface $organizationFetcher,
    ) {
    }

    public function __invoke(RegisterCommand $command): User
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        $user = $this->userRepository->findOneByEmail($email);
        if ($user instanceof User) {
            throw new UserAlreadyRegisteredException();
        }

        $organization = $this->organizationRepository->findOneBySiret($command->organizationSiret);
        $organizationRole = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value; // Default organization role
        $now = $this->dateUtils->getNow();

        if (!$organization) {
            try {
                ['name' => $name] = $this->organizationFetcher->findBySiret($command->organizationSiret);
            } catch (OrganizationNotFoundException $e) {
                throw $e;
            }

            $organizationRole = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value; // The first user in an organization becomes an admin
            $organization = (new Organization($this->idFactory->make()))
                ->setCreatedAt($now)
                ->setSiret($command->organizationSiret)
                ->setName($name);
            $this->organizationRepository->add($organization);
        }

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

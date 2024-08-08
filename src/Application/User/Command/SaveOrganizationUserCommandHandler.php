<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\IsEmailAlreadyExists;
use App\Domain\User\Specification\IsUserAlreadyRegisteredInOrganization;
use App\Domain\User\User;

final class SaveOrganizationUserCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private UserRepositoryInterface $userRepository,
        private StringUtilsInterface $stringUtils,
        private DateUtilsInterface $dateUtils,
        private PasswordHasherInterface $passwordHasher,
        private IsUserAlreadyRegisteredInOrganization $isUserAlreadyRegisteredInOrganization,
        private IsEmailAlreadyExists $isEmailAlreadyExists,
    ) {
    }

    public function __invoke(SaveOrganizationUserCommand $command): void
    {
        $email = $this->stringUtils->normalizeEmail($command->email);
        $organizationUser = $command->organizationUser;

        // Update user

        if ($organizationUser) {
            $user = $organizationUser->getUser();

            if ($email !== $user->getEmail() && true === $this->isEmailAlreadyExists->isSatisfiedBy($email)) {
                throw new EmailAlreadyExistsException();
            }

            $user->setEmail($email);
            $user->setFullName($command->fullName);
            $organizationUser->setRoles($command->roles);

            return;
        }

        // Create user

        // Check if the user's email address already exists in the organization
        if (true === $this->isUserAlreadyRegisteredInOrganization->isSatisfiedBy($email, $command->organization)) {
            throw new UserAlreadyRegisteredException();
        }

        // Create a new user or add one to the organization if an account already exists
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user instanceof User) {
            $user = (new User($this->idFactory->make()))
                ->setEmail($email)
                ->setFullName($command->fullName)
                ->setPassword($this->passwordHasher->hash($command->password))
                ->setRoles([UserRolesEnum::ROLE_USER->value])
                ->setRegistrationDate($this->dateUtils->getNow());

            $this->userRepository->add($user);
        }

        $this->organizationUserRepository->add(
            (new OrganizationUser($this->idFactory->make()))
                ->setUser($user)
                ->setOrganization($command->organization)
                ->setRoles($command->roles),
        );
    }
}

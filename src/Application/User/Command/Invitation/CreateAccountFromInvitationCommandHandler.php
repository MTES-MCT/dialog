<?php

declare(strict_types=1);

namespace App\Application\User\Command\Invitation;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\InvitationNotFoundException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Invitation;
use App\Domain\User\OrganizationUser;
use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final readonly class CreateAccountFromInvitationCommandHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordUserRepositoryInterface $passwordUserRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private IdFactoryInterface $idFactory,
        private DateUtilsInterface $dateUtils,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(CreateAccountFromInvitationCommand $command): User
    {
        $invitation = $this->invitationRepository->findOneByUuid($command->invitationUuid);
        if (!$invitation instanceof Invitation) {
            throw new InvitationNotFoundException();
        }

        $email = $invitation->getEmail();

        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser instanceof User) {
            throw new UserAlreadyRegisteredException();
        }

        $now = $this->dateUtils->getNow();
        $user = (new User($this->idFactory->make()))
            ->setFullName($invitation->getFullName())
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
            ->setOrganization($invitation->getOrganization())
            ->setRoles($invitation->getRole());

        $this->userRepository->add($user);
        $this->passwordUserRepository->add($passwordUser);
        $this->organizationUserRepository->add($organizationUser);
        $this->invitationRepository->delete($invitation);

        return $user;
    }
}

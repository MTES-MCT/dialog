<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\AccessRequest;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\AccessRequestNotFoundException;
use App\Domain\User\Exception\SiretMissingException;
use App\Domain\User\Exception\UserAlreadyRegisteredException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final class ConvertAccessRequestToUserCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private AccessRequestRepositoryInterface $accessRequestRepository,
        private UserRepositoryInterface $userRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private OrganizationRepositoryInterface $organizationRepository,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(ConvertAccessRequestToUserCommand $command): void
    {
        $accessRequest = $this->accessRequestRepository->findOneByUuid($command->uuid);
        if (!$accessRequest instanceof AccessRequest) {
            throw new AccessRequestNotFoundException();
        }

        if (!$accessRequest->getSiret()) {
            throw new SiretMissingException();
        }

        $user = $this->userRepository->findOneByEmail($accessRequest->getEmail());
        if ($user instanceof User) {
            throw new UserAlreadyRegisteredException();
        }

        $organization = $this->organizationRepository->findOneBySiret($accessRequest->getSiret());
        $organizationRole = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value; // Default organization role

        if (!$organization) {
            $organizationRole = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value; // The first user in an organization becomes an admin
            $organization = (new Organization($this->idFactory->make()))
                ->setSiret($accessRequest->getSiret())
                ->setName($accessRequest->getOrganization());
            $this->organizationRepository->add($organization);
        }

        $user = (new User($this->idFactory->make()))
            ->setFullName($accessRequest->getFullName())
            ->setPassword($accessRequest->getPassword())
            ->setEmail($accessRequest->getEmail())
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($this->dateUtils->getNow());

        $organizationUser = (new OrganizationUser($this->idFactory->make()))
            ->setUser($user)
            ->setOrganization($organization)
            ->setRoles($organizationRole);

        $this->userRepository->add($user);
        $this->organizationUserRepository->add($organizationUser);
        $this->accessRequestRepository->remove($accessRequest);
    }
}

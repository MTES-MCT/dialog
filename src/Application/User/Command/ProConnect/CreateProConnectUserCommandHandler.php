<?php

declare(strict_types=1);

namespace App\Application\User\Command\ProConnect;

use App\Application\ApiOrganizationFetcherInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
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
        private OrganizationRepositoryInterface $organizationRepository,
        private DateUtilsInterface $dateUtils,
        private ApiOrganizationFetcherInterface $organizationFetcher,
    ) {
    }

    public function __invoke(CreateProConnectUserCommand $command): User
    {
        ['given_name' => $givenName, 'family_name' => $familyName, 'organization_siret' => $siret] = $command->userInfo;

        $user = $this->userRepository->findOneByEmail($command->email);
        if ($user instanceof User) {
            return $user;
        }

        $organization = $this->organizationRepository->findOneBySiret($siret);
        $organizationRole = OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value; // Default organization role
        $now = $this->dateUtils->getNow();

        if (!$organization) {
            try {
                ['name' => $name] = $this->organizationFetcher->findBySiret($siret);
            } catch (OrganizationNotFoundException $e) {
                throw $e;
            }

            $organizationRole = OrganizationRolesEnum::ROLE_ORGA_ADMIN->value; // The first user in an organization becomes an admin
            $organization = (new Organization($this->idFactory->make()))
                ->setCreatedAt($now)
                ->setSiret($siret)
                ->setName($name);
            $this->organizationRepository->add($organization);
        }

        $user = (new User($this->idFactory->make()))
            ->setFullName(\sprintf('%s %s', $givenName, $familyName))
            ->setEmail($command->email)
            ->setRoles([UserRolesEnum::ROLE_USER->value])
            ->setRegistrationDate($now)
            ->setVerified();

        $proConnectUser = new ProConnectUser(
            uuid: $this->idFactory->make(),
            user: $user,
        );

        $user->setProConnectUser($proConnectUser);

        $organizationUser = (new OrganizationUser($this->idFactory->make()))
            ->setUser($user)
            ->setOrganization($organization)
            ->setRoles($organizationRole);

        $this->userRepository->add($user);
        $this->proConnectUserRepository->add($proConnectUser);
        $this->organizationUserRepository->add($organizationUser);

        return $user;
    }
}

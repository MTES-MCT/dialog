<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Organization\ApiClient\View\ApiClientCreatedView;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Application\User\Query\GetOrganizationUserQuery;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\UserAlreadyHasApiClientForOrganizationException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\TokenGenerator;
use App\Infrastructure\Security\User\ApiClientUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class CreateApiClientForUserCommandHandler
{
    public function __construct(
        private ApiClientRepositoryInterface $apiClientRepository,
        private IdFactoryInterface $idFactory,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private DateUtilsInterface $dateUtils,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(CreateApiClientForUserCommand $command): ApiClientCreatedView
    {
        $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($command->organizationUuid));
        $organizationUser = $this->queryBus->handle(new GetOrganizationUserQuery(
            $command->organizationUuid,
            $command->userUuid,
        ));
        $user = $organizationUser->getUser();

        $existing = $this->apiClientRepository->findOneByOrganizationAndUser($organization, $user);
        if ($existing !== null) {
            throw new UserAlreadyHasApiClientForOrganizationException();
        }

        $plainSecret = (new TokenGenerator())->generate();
        $hashedSecret = $this->passwordHasherFactory->getPasswordHasher(ApiClientUser::class)->hash($plainSecret);

        $apiClient = (new ApiClient($this->idFactory->make()))
            ->setClientId($this->idFactory->make())
            ->setClientSecret($hashedSecret)
            ->setOrganization($organization)
            ->setUser($user)
            ->setIsActive(true)
            ->setCreatedAt($this->dateUtils->getNow());

        $this->apiClientRepository->add($apiClient);

        return new ApiClientCreatedView(
            clientId: $apiClient->getClientId(),
            clientSecret: $plainSecret,
        );
    }
}

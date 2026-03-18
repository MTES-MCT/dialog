<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Command;

use App\Application\Organization\ApiClient\View\ApiClientCreatedView;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\TokenGenerator;
use App\Infrastructure\Security\User\ApiClientUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class RegenerateApiClientSecretCommandHandler
{
    public function __construct(
        private ApiClientRepositoryInterface $apiClientRepository,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function __invoke(RegenerateApiClientSecretCommand $command): ApiClientCreatedView
    {
        $apiClient = $this->apiClientRepository->findOneByUuid($command->apiClientUuid);
        if ($apiClient === null) {
            throw new ApiClientNotFoundException();
        }

        $plainSecret = (new TokenGenerator())->generate();
        $hashedSecret = $this->passwordHasherFactory->getPasswordHasher(ApiClientUser::class)->hash($plainSecret);
        $apiClient->setClientSecret($hashedSecret);

        return new ApiClientCreatedView(
            clientId: $apiClient->getClientId(),
            clientSecret: $plainSecret,
        );
    }
}

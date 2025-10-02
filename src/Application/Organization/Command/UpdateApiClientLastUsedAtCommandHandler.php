<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\DateUtilsInterface;
use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;

final class UpdateApiClientLastUsedAtCommandHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $apiClientRepository,
        private readonly DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(UpdateApiClientLastUsedAtCommand $command): ApiClient
    {
        $apiClient = $this->apiClientRepository->findOneByClientId($command->clientId);
        if (!$apiClient instanceof ApiClient) {
            throw new ApiClientNotFoundException();
        }

        $apiClient->setLastUsedAt($this->dateUtils->getNow());

        return $apiClient;
    }
}

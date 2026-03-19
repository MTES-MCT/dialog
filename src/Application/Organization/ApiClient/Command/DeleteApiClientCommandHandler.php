<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Command;

use App\Domain\Organization\Exception\ApiClientNotFoundException;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;

final class DeleteApiClientCommandHandler
{
    public function __construct(
        private ApiClientRepositoryInterface $apiClientRepository,
    ) {
    }

    public function __invoke(DeleteApiClientCommand $command): void
    {
        $apiClient = $this->apiClientRepository->findOneByUuid($command->apiClientUuid);
        if ($apiClient === null) {
            throw new ApiClientNotFoundException();
        }

        $this->apiClientRepository->remove($apiClient);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\Query;

use App\Application\Organization\ApiClient\View\ApiClientView;
use App\Application\QueryBusInterface;
use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;

final class GetOrganizationApiClientsQueryHandler
{
    public function __construct(
        private ApiClientRepositoryInterface $apiClientRepository,
        private OrganizationUserRepositoryInterface $organizationUserRepository,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetOrganizationApiClientsQuery $query): array
    {
        $organization = $this->queryBus->handle(new GetOrganizationByUuidQuery($query->organizationUuid));
        $apiClients = $this->apiClientRepository->findByOrganization($organization);

        $views = [];
        foreach ($apiClients as $apiClient) {
            $user = $apiClient->getUser();
            $userFullName = null;
            $userEmail = null;
            $isOwner = false;

            if ($user !== null) {
                $userFullName = $user->getFullName();
                $userEmail = $user->getEmail();
                $organizationUser = $this->organizationUserRepository->findOrganizationUser(
                    $organization->getUuid(),
                    $user->getUuid(),
                );
                $isOwner = $organizationUser?->isOwner() ?? false;
            }

            $views[] = new ApiClientView(
                uuid: $apiClient->getUuid(),
                clientId: $apiClient->getClientId(),
                userFullName: $userFullName,
                userEmail: $userEmail,
                isOwner: $isOwner,
                createdAt: $apiClient->getCreatedAt(),
                lastUsedAt: $apiClient->getLastUsedAt(),
                isActive: $apiClient->isActive(),
            );
        }

        return $views;
    }
}

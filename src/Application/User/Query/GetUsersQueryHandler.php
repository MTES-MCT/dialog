<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Application\User\View\UserListView;
use App\Domain\User\Repository\UserRepositoryInterface;

class GetUsersQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetUsersQuery $query): array
    {
        $users =
        $this->repository->findUsers(
        );
        $data = [];
        foreach ($users as $user) {
            $data[] = new UserListView(
                $user->getUuid(),
                $user->getFullName(),
                $user->getEmail(),
            );
        }

        return $data;
    }
}

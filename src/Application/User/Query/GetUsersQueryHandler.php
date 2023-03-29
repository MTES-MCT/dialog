<?php

namespace App\Application\User\Query;

use App\Domain\User\Repository\UserRepositoryInterface;

class GetUsersQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ){
    }

    public function __invoke(GetUsersQuery $query): array
    {
        $user = 
        $this->repository->findUsers(
        );

    return $user;
    }
}
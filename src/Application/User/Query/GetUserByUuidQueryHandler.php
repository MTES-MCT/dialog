<?php

namespace App\Application\User\Query;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

class GetUserByUuidQueryHandler{
    
    public function __construct(
        private UserRepositoryInterface $userRepositoryInterface,
    ){
    }

    public function __invoke(GetUserByUuidQuery $query) : User
    {
       $user = $this->userRepositoryInterface->findUserByUuid($query->uuid);
       return $user;
    }
}
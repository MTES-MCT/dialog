<?php

namespace App\Application\User\Command;

use App\Domain\User\Exception\UserAlreadyExistException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;


class UpdateUserCommandHandler 
{
    public function __construct(
        private UserRepositoryInterface $userRepositoryInterface,
    )
    {
    }
    public function __invoke(UpdateUserCommand $command)
    {
        $user=$command->user;
            //On va vérifier que le nouvel email renseigné n'existe pas déjà en BDD dans le cas ou il aurait été modifié
            if($command->email !== $user->getEmail())
            {
                $userEdit = $this->userRepositoryInterface->findOneByEmail($command->email);

                if($userEdit!==null)
                {
                    throw new UserAlreadyExistException();
                }
            }


            $user->update($command->fullName,$command->email,$command->password);
            $this->userRepositoryInterface->save($user);
            return;
        
    }
}
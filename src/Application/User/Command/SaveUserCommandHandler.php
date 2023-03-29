<?php

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Exception\UserAlreadyExistException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Infrastructure\Adapter\PasswordHasher;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class SaveUserCommandHandler 
{
    public function __construct(
        private UserRepositoryInterface $userRepositoryInterface,
        private IdFactoryInterface $idFactory,
        private PasswordHasherInterface $passwordHasher,
        private OrganizationRepositoryInterface $organizationRepositoryInterface,
    ){
    }
    public function __invoke(SaveUserCommand $command): void
    {
        $organization = $this->organizationRepositoryInterface->findByUuid("e0d93630-acf7-4722-81e8-ff7d5fa64b66");
        $user = $this->userRepositoryInterface->findOneByEmail($command->email);

        //Pour vérifier l'unicité
        if( $user instanceof User )
        {
            throw new UserAlreadyExistException();

        }else{
            
            $user = $this->userRepositoryInterface->save(new User(
                uuid: $this->idFactory->make(),
                fullName: $command->fullName,
                email: $command->email,
                password: $this->passwordHasher->hash($command->password),
            ));
            $organization->addUser($user);
        }
    }
    
}
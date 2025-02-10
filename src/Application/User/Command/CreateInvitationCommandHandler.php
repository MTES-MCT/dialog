<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\Exception\InvitationAlreadyExistsException;
use App\Domain\User\Exception\OrganizationUserAlreadyExistException;
use App\Domain\User\Invitation;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Repository\User\OrganizationUserRepository;

final readonly class CreateInvitationCommandHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private OrganizationUserRepository $organizationUserRepository,
        private IdFactoryInterface $idFactory,
        private DateUtilsInterface $dateUtils,
        private StringUtilsInterface $stringUtils,
    ) {
    }

    public function __invoke(CreateInvitationCommand $command): Invitation
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        if ($this->invitationRepository->findOneByEmailAndOrganization($email, $command->organization)) {
            throw new InvitationAlreadyExistsException();
        }

        if ($this->organizationUserRepository->findByEmailAndOrganization($email, $command->organization->getUuid())) {
            throw new OrganizationUserAlreadyExistException();
        }

        return $this->invitationRepository->add(
            new Invitation(
                uuid: $this->idFactory->make(),
                email: $email,
                role: $command->role,
                fullName: $command->fullName,
                createdAt: $this->dateUtils->getNow(),
                owner: $command->owner,
                organization: $command->organization,
            ),
        );
    }
}

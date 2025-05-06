<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;

final class SaveMailingListCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private MailingListRepositoryInterface $mailingListRepository,
    ) {
    }

    public function __invoke(SaveMailingListCommand $command): MailingList
    {
        if ($mailingList = $command->mailingList) {
            $mailingList->update(
                name: $command->name,
                email: $command->email,
                role: $command->role,
            );

            return $mailingList;
        }

        return $this->mailingListRepository->add(
            new MailingList(
                uuid: $this->idFactory->make(),
                name: $command->name,
                email: $command->email,
                role: $command->role,
                organization: $command->organization,
            ),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Domain\Organization\MailingList\Exception;
use App\Domain\Organization\MailingList\MailingList;
use App\Domain\Organization\MailingList\Repository\MailingListRepositoryInterface;

final class DeleteMailingListCommandHandler
{
    public function __construct(
        private MailingListRepositoryInterface $mailingListRepository,
    ) {
    }

    public function __invoke(DeleteMailingListCommand $command): void
    {
        $mailingList = $this->mailingListRepository->findOneByUuid($command->uuid);

        if (!$mailingList instanceof MailingList) {
            throw new Exception\MailingListNotFoundException();
        }

        $this->mailingListRepository->remove($mailingList);
    }
}

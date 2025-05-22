<?php

declare(strict_types=1);

namespace App\Application\Organization\MailingList\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\User;

final class SendRegulationOrderToMailingListCommand implements CommandInterface
{
    public array $recipients = [];
    public string $emails;

    public function __construct(
        public readonly RegulationOrder $regulationOrder,
        public readonly RegulationOrderRecord $regulationOrderRecord,
        public readonly User $user,
    ) {
    }
}

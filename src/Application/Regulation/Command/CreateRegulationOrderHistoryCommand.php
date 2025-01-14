<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\User\User;

final class CreateRegulationOrderHistoryCommand implements CommandInterface
{
    public function __construct(
        public RegulationOrder $regulationOrder,
        public User $user,
        public string $action,
    ) {
    }
}

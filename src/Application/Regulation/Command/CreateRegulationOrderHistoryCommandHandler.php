<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use App\Infrastructure\Adapter\IdFactory;

final class CreateRegulationOrderHistoryCommandHandler
{
    public function __construct(
        private IdFactory $idFactory,
        private RegulationOrderHistoryRepositoryInterface $regulationOrderHistoryRepository,
        private \DateTimeInterface $now,
    ) {
    }

    public function __invoke(CreateRegulationOrderHistoryCommand $command): RegulationOrderHistory
    {
        $regulationOrderHistory = $this->regulationOrderHistoryRepository->add(
            new RegulationOrderHistory(
                uuid: $this->idFactory->make(),
                regulationOrder: $command->regulationOrder,
                user: $command->user,
                action: $command->action,
                date: $this->now,
            ),
        );

        return $regulationOrderHistory;
    }
}

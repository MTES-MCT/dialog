<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Domain\Regulation\Repository\PeriodRepositoryInterface;

final class DeletePeriodCommandHandler
{
    public function __construct(
        private PeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(DeletePeriodCommand $command): void
    {
        $this->periodRepository->delete($command->period);
    }
}

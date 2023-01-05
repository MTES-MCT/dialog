<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

final class SaveRegulationStep2CommandHandler
{
    public function __invoke(SaveRegulationStep2Command $command): void
    {
        $command->regulationOrderRecord->updateLastFilledStep(2);
    }
}

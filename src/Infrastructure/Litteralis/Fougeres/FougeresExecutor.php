<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis\Fougeres;

use App\Infrastructure\Litteralis\LitteralisExecutor;

final class FougeresExecutor
{
    public function __construct(
        private LitteralisExecutor $executor,
        private string $fougeresOrgId,
        string $fougeresCredentials,
    ) {
        $this->executor->configure($fougeresCredentials);
    }

    public function execute(\DateTimeInterface $laterThan): string
    {
        return $this->executor->execute($this->fougeresOrgId, $laterThan);
    }
}

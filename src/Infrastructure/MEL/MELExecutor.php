<?php

declare(strict_types=1);

namespace App\Infrastructure\MEL;

use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisExecutor;

final class MELExecutor
{
    public function __construct(
        private LitteralisExecutor $executor,
        private string $melOrgId,
        string $melCredentials,
    ) {
        $this->executor->configure($melCredentials);
    }

    public function execute(\DateTimeInterface $laterThan, Reporter $reporter): string
    {
        return $this->executor->execute($this->melOrgId, $laterThan, $reporter);
    }
}

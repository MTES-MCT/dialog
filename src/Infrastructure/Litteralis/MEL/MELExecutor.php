<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis\MEL;

use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisExecutor;

final class MELExecutor
{
    public const INTEGRATION_NAME = 'Litteralis MEL';

    public function __construct(
        private LitteralisExecutor $executor,
        private string $melOrgId,
        string $melCredentials,
    ) {
        $this->executor->configure($melCredentials);
    }

    public function execute(\DateTimeInterface $laterThan, Reporter $reporter): string
    {
        return $this->executor->execute(self::INTEGRATION_NAME, $this->melOrgId, $laterThan, $reporter);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis\LonsLeSaunier;

use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisExecutor;

final class LonsLeSaunierExecutor
{
    private const INTEGRATION_NAME = 'Litteralis Lons-le-Saunier';

    public function __construct(
        private LitteralisExecutor $executor,
        private string $lonsLeSaunierOrgId,
        string $lonsLeSaunierCredentials,
    ) {
        $this->executor->configure($lonsLeSaunierCredentials);
    }

    public function execute(\DateTimeInterface $laterThan, Reporter $reporter): string
    {
        return $this->executor->execute(self::INTEGRATION_NAME, $this->lonsLeSaunierOrgId, $laterThan, $reporter);
    }
}

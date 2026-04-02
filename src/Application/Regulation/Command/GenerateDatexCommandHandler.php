<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\Regulation\DatexGeneratorInterface;

final class GenerateDatexCommandHandler
{
    public function __construct(
        private readonly DatexGeneratorInterface $datexGenerator,
    ) {
    }

    public function __invoke(GenerateDatexCommand $command): void
    {
        $this->datexGenerator->generate();
    }
}

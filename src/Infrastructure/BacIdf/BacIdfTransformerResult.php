<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;

final class BacIdfTransformerResult
{
    public function __construct(
        public readonly ?ImportBacIdfRegulationCommand $command,
        public readonly array $messages,
    ) {
    }
}

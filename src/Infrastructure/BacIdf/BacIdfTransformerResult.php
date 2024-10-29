<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\User\Command\SaveOrganizationCommand;
use App\Domain\User\Organization;

final class BacIdfTransformerResult
{
    public function __construct(
        public readonly ?ImportBacIdfRegulationCommand $command,
        public readonly array $errors,
        public readonly ?Organization $organization = null,
        public readonly ?SaveOrganizationCommand $organizationCommand = null,
    ) {
    }
}

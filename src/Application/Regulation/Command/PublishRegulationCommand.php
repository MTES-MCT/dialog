<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;

final class PublishRegulationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $regulationOrderRecordUuid,
        public string $status,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep1Command implements CommandInterface
{
    public ?string $issuingAuthority;
    public ?string $description;

    public function __construct(
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($regulationOrderRecord);
        $command->issuingAuthority = $regulationOrder?->getIssuingAuthority();
        $command->description = $regulationOrder?->getDescription();

        return $command;
    }
}

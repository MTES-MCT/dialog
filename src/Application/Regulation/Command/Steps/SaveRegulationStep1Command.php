<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\User\Organization;

final class SaveRegulationStep1Command implements CommandInterface
{
    public ?string $issuingAuthority;
    public ?string $description;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?RegulationOrder $regulationOrder = null,
    ) {
    }

    public static function create(
        Organization $organization,
        RegulationOrder $regulationOrder = null,
    ): self {
        $command = new self($organization, $regulationOrder);
        $command->issuingAuthority = $regulationOrder?->getIssuingAuthority()
            ?? $organization->getName();
        $command->description = $regulationOrder?->getDescription();

        return $command;
    }
}

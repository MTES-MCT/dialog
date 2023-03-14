<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

final class SaveRegulationStep1Command implements CommandInterface
{
    public ?string $issuingAuthority;
    public ?string $description;
    public ?\DateTimeInterface $startDate;
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        Organization $organization,
        RegulationOrderRecord $regulationOrderRecord = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($organization, $regulationOrderRecord);
        $command->issuingAuthority = $regulationOrder?->getIssuingAuthority()
            ?? $organization->getName();
        $command->description = $regulationOrder?->getDescription();
        $command->startDate = $regulationOrder?->getStartDate();
        $command->endDate = $regulationOrder?->getEndDate();

        return $command;
    }
}

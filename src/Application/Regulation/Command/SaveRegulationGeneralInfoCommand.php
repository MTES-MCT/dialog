<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

final class SaveRegulationGeneralInfoCommand implements CommandInterface
{
    public ?string $identifier;
    public ?string $description;
    public ?Organization $organization;
    public ?\DateTimeInterface $startDate;
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord = null,
        \DateTimeImmutable $startDate = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($regulationOrderRecord);
        $command->organization = $regulationOrderRecord?->getOrganization();
        $command->identifier = $regulationOrder?->getIdentifier();
        $command->description = $regulationOrder?->getDescription();
        $command->startDate = $startDate ?? $regulationOrder?->getStartDate();
        $command->endDate = $regulationOrder?->getEndDate();

        return $command;
    }
}

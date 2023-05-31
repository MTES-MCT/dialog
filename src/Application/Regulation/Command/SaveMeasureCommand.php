<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?Location $location;
    public array $periods = [];
    public ?\DateTimeInterface $createdAt = null;
    public ?bool $allVehicles;
    public array $restrictedVehicleTypes;
    public ?string $otherRestrictedVehicleTypeText;
    public array $exemptedVehicleTypes;
    public ?string $otherExemptedVehicleTypeText;

    public function __construct(
        public readonly ?Measure $measure = null,
    ) {
        $this->location = $measure?->getLocation();
        $this->initFromEntity($measure);

        if ($measure) {
            foreach ($measure->getPeriods() as $period) {
                $this->periods[] = new SavePeriodCommand($period);
            }
        }
    }

    public function initFromEntity(Measure $measure = null): void
    {
        $this->type = $measure?->getType();
        $this->createdAt = $measure?->getCreatedAt();
        $this->allVehicles = $measure ? (empty($measure->getRestrictedVehicleTypes()) ? true : false) : null;
        $this->restrictedVehicleTypes = $measure?->getRestrictedVehicleTypes() ?: [];
        $this->otherRestrictedVehicleTypeText = $measure?->getOtherRestrictedVehicleTypeText();
        $this->exemptedVehicleTypes = $measure?->getExemptedVehicleTypes() ?: [];
        $this->otherExemptedVehicleTypeText = $measure?->getOtherExemptedVehicleTypeText();
    }

    public function cleanVehicleTypes(): void
    {
        if ($this->allVehicles) {
            $this->restrictedVehicleTypes = [];
            $this->otherRestrictedVehicleTypeText = null;
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->restrictedVehicleTypes)) {
            $this->otherRestrictedVehicleTypeText = null;
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->exemptedVehicleTypes)) {
            $this->otherExemptedVehicleTypeText = null;
        }
    }
}

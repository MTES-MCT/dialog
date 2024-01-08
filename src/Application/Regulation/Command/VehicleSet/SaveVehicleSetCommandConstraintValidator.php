<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\VehicleSet;

use App\Domain\Regulation\Specification\IsMeasureForAllVehicles;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SaveVehicleSetCommandConstraintValidator extends ConstraintValidator
{
    public function __construct(private readonly IsMeasureForAllVehicles $isMeasureForAllVehicles)
    {
    }

    /** @param SaveVehicleSetCommand $value */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value->allVehicles === false && $this->isMeasureForAllVehicles->isSatisfiedBy($value)) {
            $this->context
                ->buildViolation('regulation.vehicle_set.all_vehicles.no.error.not_blank')
                ->atPath('allVehicles')
                ->addViolation();
        }
    }
}

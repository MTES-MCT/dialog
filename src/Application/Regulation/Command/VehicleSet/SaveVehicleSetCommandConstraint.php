<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\VehicleSet;

use Symfony\Component\Validator\Constraint;

class SaveVehicleSetCommandConstraint extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}

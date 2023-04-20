<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

final class SaveRegulationGeneralInfoCommandConstraint extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

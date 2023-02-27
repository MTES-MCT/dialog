<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

final class SaveRegulationStep3CommandConstraint extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

class EmailsConstraint extends Constraint
{
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}

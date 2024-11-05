<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

final class ValidGeoJsonConstraint extends Constraint
{
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}

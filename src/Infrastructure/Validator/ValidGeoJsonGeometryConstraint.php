<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

final class ValidGeoJsonGeometryConstraint extends Constraint
{
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}

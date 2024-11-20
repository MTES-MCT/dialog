<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Infrastructure\Adapter\GeoJSONGeometryConverter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidGeoJsonGeometryConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private GeoJSONGeometryConverter $converter,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidGeoJsonGeometryConstraint) {
            throw new UnexpectedValueException($constraint, ValidGeoJsonGeometryConstraint::class);
        }

        if (!$this->converter->isValid($value)) {
            $this->context->buildViolation('geojson.error.invalid')
                ->addViolation();
        }
    }
}

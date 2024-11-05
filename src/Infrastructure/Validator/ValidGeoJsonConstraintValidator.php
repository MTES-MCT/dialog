<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidGeoJsonConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidGeoJsonConstraint) {
            throw new UnexpectedValueException($constraint, ValidGeoJsonConstraint::class);
        }

        try {
            $this->connection->fetchAssociative(
                'SELECT ST_GeomFromGeoJSON(:value)',
                ['value' => $value],
            );
        } catch (\Exception $e) {
            $this->context->buildViolation('geojson.error.invalid')
                ->addViolation();
        }
    }
}

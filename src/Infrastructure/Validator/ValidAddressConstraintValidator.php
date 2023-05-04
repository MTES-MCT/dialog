<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Domain\Regulation\Exception\LocationAddressParsingException;
use App\Domain\Regulation\LocationAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidAddressConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidAddressConstraint) {
            throw new UnexpectedTypeException($constraint, ValidAddressConstraint::class);
        }

        if (!$value) {
            return;
        }

        try {
            LocationAddress::fromString($value);
        } catch (LocationAddressParsingException) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

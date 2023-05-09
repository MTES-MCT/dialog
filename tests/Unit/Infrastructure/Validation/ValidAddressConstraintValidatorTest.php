<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Validation;

use App\Infrastructure\Validator\ValidAddressConstraintValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidAddressConstraintValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidAddressConstraintValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('', new NotBlank());
    }

    // Other cases are covered in integration tests.
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailsConstraintValidator extends ConstraintValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        $emails = array_map('trim', explode(',', $value));
        $emailConstraint = new Email();

        foreach ($emails as $email) {
            if (empty($email)) {
                continue;
            }

            $errors = $this->validator->validate($email, $emailConstraint);

            if (\count($errors) > 0) {
                $this->context->buildViolation('invalid.emails')
                    ->setParameter('%value%', $email)
                    ->addViolation();
            }
        }
    }
}

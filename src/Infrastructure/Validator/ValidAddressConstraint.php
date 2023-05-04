<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class ValidAddressConstraint extends Constraint
{
    public $message = 'location.error.valid_address';
}

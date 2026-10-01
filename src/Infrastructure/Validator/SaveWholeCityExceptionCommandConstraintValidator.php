<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveWholeCityExceptionCommandConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveWholeCityExceptionCommand) {
            throw new UnexpectedValueException($command, SaveWholeCityExceptionCommand::class);
        }

        foreach (['departmentalRoad', 'nationalRoad'] as $roadType) {
            if ($command->roadType !== $roadType) {
                continue;
            }

            NumberedRoadRequiredFieldsValidator::validate($this->context, $command->$roadType, $roadType);
        }
    }
}

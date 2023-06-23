<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveRegulationGeneralInfoCommandConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveRegulationGeneralInfoCommand) {
            throw new UnexpectedValueException($command, SaveRegulationGeneralInfoCommand::class);
        }

        if ($command->endDate !== null && $command->endDate < $command->startDate) {
            $viewStartDate = \DateTimeImmutable::createFromInterface($command->startDate)->format('d/m/Y');

            $this->context->buildViolation('regulation.error.end_date_before_start_date')
                ->setParameter('{{ compared_value }}', $viewStartDate)
                ->atPath('endDate')
                ->addViolation();
        }
    }
}

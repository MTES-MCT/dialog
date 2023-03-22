<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\SaveRegulationOrderCommand;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveRegulationOrderCommandConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveRegulationOrderCommand) {
            throw new UnexpectedValueException($command, SaveRegulationOrderCommand::class);
        }

        if ($command->endDate !== null && $command->endDate < $command->startDate) {
            $viewStartDate = \DateTimeImmutable::createFromInterface($command->startDate)
                ->setTimezone(new \DateTimeZone($this->clientTimezone))
                ->format('d/m/Y');

            $this->context->buildViolation('regulation.step1.error.end_date_before_start_date')
                ->setParameter('{{ compared_value }}', $viewStartDate)
                ->atPath('endDate')
                ->addViolation();
        }
    }
}

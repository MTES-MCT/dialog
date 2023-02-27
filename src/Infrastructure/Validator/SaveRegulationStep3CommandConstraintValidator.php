<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SaveRegulationStep3CommandConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

    public function validate(mixed $command, Constraint $constraint): void
    {
        if (!$command instanceof SaveRegulationStep3Command) {
            throw new UnexpectedValueException($command, SaveRegulationStep3Command::class);
        }

        if (null === $command->endDate) {
            if (null !== $command->endTime) {
                $this->context->buildViolation('regulation.step3.error.end_time_without_end_date')
                    ->atPath('endDate')
                    ->addViolation();
            }

            return;
        }

        if ($command->startDate < $command->endDate) {
            return;
        }

        if ($command->endDate < $command->startDate) {
            $viewStartDate = \DateTimeImmutable::createFromInterface($command->startDate)
                ->setTimezone(new \DateTimeZone($this->clientTimezone))
                ->format('d/m/Y');

            $this->context->buildViolation('regulation.step3.error.end_date_before_start_date')
                ->setParameter('{{ compared_value }}', $viewStartDate)
                ->atPath('endDate')
                ->addViolation();

            return;
        }

        if (null === $command->endTime || null === $command->startTime) {
            return;
        }

        if ($command->startTime < $command->endTime) {
            return;
        }

        $viewStartTime = \DateTimeImmutable::createFromInterface($command->startTime)
            ->setTimezone(new \DateTimeZone($this->clientTimezone))
            ->format('H\\hi');

        $this->context->buildViolation('regulation.step3.error.end_time_before_start_time')
            ->setParameter('{{ compared_value }}', $viewStartTime)
            ->atPath('endTime')
            ->addViolation();
    }
}

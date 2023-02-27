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

        // First, check the dates.
        // The end date must be strictly after the start date.

        if (!$command->endDate) {
            if ($command->endTime) {
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
            $startDate = new \DateTimeImmutable($command->startDate->format('Y-m-d'));
            $viewStartDate = $startDate->setTimezone(new \DateTimeZone($this->clientTimezone))->format('d/m/Y');

            $this->context->buildViolation('regulation.step3.error.end_date_before_start_date')
                ->setParameter('{{ compared_value }}', $viewStartDate)
                ->atPath('endDate')
                ->addViolation();

            return;
        }

        // Same day: check the times.
        // The end time (if set) must be strictly after the start time (if set).

        if (!$command->endTime || !$command->startTime) {
            return;
        }

        if ($command->endTime > $command->startTime) {
            return;
        }

        $startTime = new \DateTimeImmutable($command->startTime->format('H:i:s'));
        $viewStartTime = $startTime->setTimezone(new \DateTimeZone($this->clientTimezone))->format('H\\hi');

        $this->context->buildViolation('regulation.step3.error.end_time_before_start_time')
            ->setParameter('{{ compared_value }}', $viewStartTime)
            ->atPath('endTime')
            ->addViolation();
    }
}

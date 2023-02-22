<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class Step3FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'startDate',
                DateType::class,
                options: [
                    'label' => 'regulation.step3.start_date',
                    'help' => 'regulation.step3.start_date.help',
                    'widget' => 'single_text',
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                ],
            )
            ->add(
                'startTime',
                TimeType::class,
                options: [
                    'label' => 'regulation.step3.start_time',
                    'help' => 'regulation.step3.start_time.help',
                    'widget' => 'single_text',
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'Europe/Paris',
                    'reference_date' => new \DateTimeImmutable('2023-01-01T00:00:00'),
                    'input' => 'datetime_immutable',
                    'required' => false,
                ],
            )
            ->add(
                'endDate',
                DateType::class,
                options: [
                    'label' => 'regulation.step3.end_date',
                    'help' => 'regulation.step3.end_date.help',
                    'widget' => 'single_text',
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                    'required' => false,
                ],
            )
            ->add(
                'endTime',
                TimeType::class,
                options: [
                    'label' => 'regulation.step3.end_time',
                    'widget' => 'single_text',
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'Europe/Paris',
                    'reference_date' => new \DateTimeImmutable('2023-01-01T00:00:00'),
                    'input' => 'datetime_immutable',
                    'required' => false,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                options: [
                    'label' => 'common.form.next',
                ],
            )
        ;
    }

    public static function validate(SaveRegulationStep3Command $command, ExecutionContextInterface $context, $payload): void
    {
        // First, check the dates.
        // The end date must be strictly after the start date.

        if (!$command->endDate) {
            if ($command->endTime) {
                $context->buildViolation('regulation.step3.error.end_time_without_end_date')
                    ->atPath('endDate')
                    ->addViolation();
            }

            return;
        }

        if ($command->startDate < $command->endDate) {
            return;
        }

        if ($command->endDate < $command->startDate) {
            $context->buildViolation('regulation.step3.error.end_date_before_start_date')
                ->setParameter('{{ compared_value }}', $command->startDate->format('d/m/Y'))
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
        $viewStartTime = $startTime->setTimezone(new \DateTimeZone('Europe/Paris'))->format('H\\hi');

        $context->buildViolation('regulation.step3.error.end_time_before_start_time')
            ->setParameter('{{ compared_value }}', $viewStartTime)
            ->atPath('endTime')
            ->addViolation();
    }
}

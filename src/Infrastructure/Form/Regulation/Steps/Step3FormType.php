<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation\Steps;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;

final class Step3FormType extends AbstractType
{
    public function __construct(
        private string $clientTimezone,
    ) {
    }

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
                    'view_timezone' => $this->clientTimezone,
                ],
            )
            ->add(
                'startTime',
                TimeType::class,
                options: [
                    'label' => 'regulation.step3.start_time',
                    'help' => 'regulation.step3.start_time.help',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
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
                    'view_timezone' => $this->clientTimezone,
                    'required' => false,
                ],
            )
            ->add(
                'endTime',
                TimeType::class,
                options: [
                    'label' => 'regulation.step3.end_time',
                    'widget' => 'single_text',
                    'view_timezone' => $this->clientTimezone,
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
}

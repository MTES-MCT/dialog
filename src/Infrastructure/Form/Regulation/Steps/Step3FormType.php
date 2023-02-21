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
                    'view_timezone' => 'Europe/Paris',
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
                    'reference_date' => new \DateTimeImmutable('Sunday, 01-Jan-2023 00:00:00 UTC'),
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
                    'view_timezone' => 'Europe/Paris',
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
                    'reference_date' => new \DateTimeImmutable('Sunday, 01-Jan-2023 00:00:00 UTC'),
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

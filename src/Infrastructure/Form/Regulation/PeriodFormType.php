<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PeriodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'label' => 'regulation.period.startDate',
                'widget' => 'single_text',
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'regulation.period.startTime',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'regulation.period.endDate',
                'widget' => 'single_text',
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'regulation.period.endTime',
                'widget' => 'single_text',
            ])
            ->add('recurrenceType', ChoiceType::class,
                options: $this->getRecurrenceTypeOptions(),
            )
            ->add('timeSlots', CollectionType::class, [
                'entry_type' => TimeSlotFormType::class,
                'entry_options' => ['label' => false],
                'prototype_name' => '__timeSlot_name__',
                'label' => 'regulation.timeSlots',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
            ->add('dailyRange', DailyRangeFormType::class)
        ;
    }

    private function getRecurrenceTypeOptions(): array
    {
        $choices = [];

        foreach (PeriodRecurrenceTypeEnum::cases() as $case) {
            $choices[sprintf('regulation.period.recurrenceType.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'label' => 'regulation.period.recurrenceType',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SavePeriodCommand::class,
        ]);
    }
}

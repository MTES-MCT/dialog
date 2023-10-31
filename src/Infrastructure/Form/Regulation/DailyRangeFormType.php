<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Regulation;

use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DailyRangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applicableDays', ChoiceType::class, $this->getDaysOptions())
            ->add('timeSlots', CollectionType::class, [
                'entry_type' => TimeSlotFormType::class,
                'entry_options' => ['label' => false],
                'prototype_name' => '__timeSlot_name__',
                'label' => 'regulation.timeSlots',
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])
        ;
    }

    private function getDaysOptions(): array
    {
        $choices = [];

        foreach (ApplicableDayEnum::cases() as $case) {
            $choices[sprintf('regulation.period.days.%s', $case->value)] = $case->value;
        }

        return [
            'choices' => $choices,
            'expanded' => true,
            'multiple' => true,
            'label' => 'regulation.period.days',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaveDailyRangeCommand::class,
        ]);
    }
}
